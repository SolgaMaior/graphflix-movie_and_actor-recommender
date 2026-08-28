"""
seed_cognodb.py

Single-file seeder that:
1. Loads a filtered slice of the Kaggle "The Movies Dataset"
2. Extracts movies, actors, directors, users, and WATCHED/ACTED_IN/DIRECTED relationships
3. Loads everything into a CognoDB (Neo4j-compatible) graph database

Requirements:
    pip install pandas neo4j python-dotenv

Expected CSV files (from https://www.kaggle.com/datasets/rounakbanik/the-movies-dataset)
in the same directory as this script:
    - movies_metadata.csv
    - credits.csv
    - ratings_small.csv
    - links.csv

Usage:
    python seed_cognodb.py
"""

import ast
import os
from pathlib import Path

import pandas as pd
from dotenv import load_dotenv
from neo4j import GraphDatabase

BASE_DIR = Path(__file__).resolve().parent
PROJECT_DIR = BASE_DIR.parent

# Prefer the project's local settings, while still allowing `.env` when this
# script is used outside the Laravel application.
load_dotenv(PROJECT_DIR / ".env")
load_dotenv(PROJECT_DIR / ".env.local", override=True)

# CONFIG — replace these with your real CognoDB Cloud connection details
# ---------------------------------------------------------------------------
COGNODB_URI = os.getenv("COGNODB_URI")
COGNODB_USER = os.getenv("COGNODB_USER")
COGNODB_PASSWORD = os.getenv("COGNODB_PASSWORD") or os.getenv("COGNODB_PASS")

# How much data to pull
NUM_MOVIES = 150
# Maximum number of users to include. The most active users in the selected
# movie set are chosen, so this does not create synthetic users.
NUM_USERS = 250
# Keep the first 10 billed cast members per movie to balance useful actor
# connections with graph size and query response time.
CAST_PER_MOVIE = 10
BATCH_SIZE = 100

# ---------------------------------------------------------------------------
# STEP 1: Load and filter the raw CSVs
# ---------------------------------------------------------------------------

def load_filtered_data():
    print("Loading CSVs...")
    movies = pd.read_csv(BASE_DIR / "movies_metadata.csv", low_memory=False)
    m_credits = pd.read_csv(BASE_DIR / "credits.csv")
    ratings = pd.read_csv(BASE_DIR / "ratings_small.csv")
    links = pd.read_csv(BASE_DIR / "links.csv")

    # Pick the most-rated movies that have a matching TMDB record in both
    # links.csv and movies_metadata.csv. Selecting the valid records before
    # filtering movies_metadata guarantees that NUM_MOVIES is the number of
    # Movie nodes that will be created.
    movie_rating_counts = ratings["movieId"].value_counts()
    movies["id"] = pd.to_numeric(movies["id"], errors="coerce")
    links["tmdbId"] = pd.to_numeric(links["tmdbId"], errors="coerce")
    valid_tmdb_ids = set(movies["id"].dropna())
    movieid_to_tmdb_all = dict(zip(links["movieId"], links["tmdbId"]))
    top_movie_ids = [
        movie_id
        for movie_id in movie_rating_counts.index
        if pd.notna(movieid_to_tmdb_all.get(movie_id))
        and movieid_to_tmdb_all[movie_id] in valid_tmdb_ids
    ][:NUM_MOVIES]

    if len(top_movie_ids) < NUM_MOVIES:
        raise ValueError(
            f"Only {len(top_movie_ids)} rated movies have matching metadata; "
            f"cannot build the requested {NUM_MOVIES} movies."
        )

    # Restrict ratings to those movies, then pick the most active users on that subset
    ratings_top = ratings[ratings["movieId"].isin(top_movie_ids)]
    user_counts = ratings_top.groupby("userId").size().sort_values(ascending=False)
    top_users = user_counts.head(NUM_USERS).index
    ratings_final = ratings_top[ratings_top["userId"].isin(top_users)]

    print(f"Movies: {len(top_movie_ids)}")
    print(f"Users: {len(top_users)}")
    print(f"Ratings: {len(ratings_final)}")
    print(f"Avg movies per user: {ratings_final.groupby('userId').size().mean():.1f}")

    # Bridge MovieLens movieId -> TMDB id via links.csv
    links_top = links[links["movieId"].isin(top_movie_ids)]
    movieid_to_tmdb = dict(zip(links_top["movieId"], links_top["tmdbId"]))
    tmdb_ids = set(links_top["tmdbId"].dropna())

    movies_filtered = movies[movies["id"].isin(tmdb_ids)].copy()

    m_credits["id"] = pd.to_numeric(m_credits["id"], errors="coerce")
    credits_filtered = m_credits[m_credits["id"].isin(tmdb_ids)].copy()

    return movies_filtered, credits_filtered, ratings_final, movieid_to_tmdb


# ---------------------------------------------------------------------------
# STEP 2: Parse cast/crew fields (stringified Python literals, not JSON)
# ---------------------------------------------------------------------------

def get_director(crew_str):
    if not isinstance(crew_str, str):
        return None
    try:
        crew = ast.literal_eval(crew_str)
    except (ValueError, SyntaxError, TypeError):
        return None
    if not isinstance(crew, list):
        return None
    for person in crew:
        if isinstance(person, dict) and person.get("job") == "Director":
            return person.get("name")
    return None


def get_top_cast(cast_str, n=CAST_PER_MOVIE):
    if not isinstance(cast_str, str):
        return []
    try:
        cast = ast.literal_eval(cast_str)
    except (ValueError, SyntaxError, TypeError):
        return []
    if not isinstance(cast, list):
        return []
    selected_cast = cast if n is None else cast[:n]
    return [c.get("name") for c in selected_cast if isinstance(c, dict) and c.get("name")]


def get_genre(genres_str):
    if not isinstance(genres_str, str):
        return "Unknown"
    try:
        genres = ast.literal_eval(genres_str)
    except (ValueError, SyntaxError, TypeError):
        return "Unknown"
    if isinstance(genres, list) and genres and isinstance(genres[0], dict):
        return genres[0].get("name", "Unknown")
    return "Unknown"


def get_year(release_date):
    if pd.isna(release_date) or not release_date:
        return None
    try:
        return int(str(release_date)[:4])
    except ValueError:
        return None


# ---------------------------------------------------------------------------
# STEP 3: Build clean node/relationship records in memory
# ---------------------------------------------------------------------------

def build_graph_records(movies_filtered, credits_filtered, ratings_final, movieid_to_tmdb):
    print("Building graph records...")

    movies_by_tmdb = movies_filtered.set_index("id")
    credits_by_tmdb = credits_filtered.set_index("id")

    movie_nodes = []
    actor_nodes = {}   # name -> id
    director_nodes = {}  # name -> id
    acted_in = []
    directed = []

    next_actor_id = 1
    next_director_id = 1

    for tmdb_id, row in movies_by_tmdb.iterrows():
        movie_id = f"m{int(tmdb_id)}"
        movie_nodes.append({
            "id": movie_id,
            "title": row.get("title", "Unknown"),
            "year": get_year(row.get("release_date")),
            "genre": get_genre(row.get("genres", "[]")),
        })

        if tmdb_id in credits_by_tmdb.index:
            crow = credits_by_tmdb.loc[tmdb_id]
            # Handle possible duplicate index entries by taking the first row
            if isinstance(crow, pd.DataFrame):
                crow = crow.iloc[0]

            director_name = get_director(crow.get("crew", "[]"))
            if director_name:
                if director_name not in director_nodes:
                    director_nodes[director_name] = f"d{next_director_id}"
                    next_director_id += 1
                directed.append({"director": director_nodes[director_name], "movie": movie_id})

            for actor_name in get_top_cast(crow.get("cast", "[]")):
                if actor_name not in actor_nodes:
                    actor_nodes[actor_name] = f"a{next_actor_id}"
                    next_actor_id += 1
                acted_in.append({"actor": actor_nodes[actor_name], "movie": movie_id})

    # Users + WATCHED relationships
    user_nodes = []
    watched = []
    seen_users = set()

    for _, r in ratings_final.iterrows():
        movie_ls_id = r["movieId"]
        tmdb_id = movieid_to_tmdb.get(movie_ls_id)
        if pd.isna(tmdb_id) or int(tmdb_id) not in movies_by_tmdb.index:
            continue

        user_id = f"u{int(r['userId'])}"
        if user_id not in seen_users:
            user_nodes.append({"id": user_id, "name": f"User {int(r['userId'])}"})
            seen_users.add(user_id)

        watched.append({
            "user": user_id,
            "movie": f"m{int(tmdb_id)}",
            "rating": float(r["rating"]),
        })

    actor_node_list = [{"id": v, "name": k} for k, v in actor_nodes.items()]
    director_node_list = [{"id": v, "name": k} for k, v in director_nodes.items()]

    print(f"Built: {len(movie_nodes)} movies, {len(actor_node_list)} actors, "
          f"{len(director_node_list)} directors, {len(user_nodes)} users, "
          f"{len(watched)} WATCHED relationships")

    return {
        "movies": movie_nodes,
        "actors": actor_node_list,
        "directors": director_node_list,
        "users": user_nodes,
        "acted_in": acted_in,
        "directed": directed,
        "watched": watched,
    }


# ---------------------------------------------------------------------------
# STEP 4: Load into CognoDB
# ---------------------------------------------------------------------------

def seed_database(tx, data):
    queries = [
        (
            "movies",
            """
            UNWIND $rows AS row
            MERGE (m:Movie {id: row.id})
            SET m.title = row.title, m.year = row.year, m.genre = row.genre
            """,
        ),
        (
            "actors",
            """
            UNWIND $rows AS row
            MERGE (a:Actor {id: row.id})
            SET a.name = row.name
            """,
        ),
        (
            "directors",
            """
            UNWIND $rows AS row
            MERGE (d:Director {id: row.id})
            SET d.name = row.name
            """,
        ),
        (
            "users",
            """
            UNWIND $rows AS row
            MERGE (u:User {id: row.id})
            SET u.name = row.name
            """,
        ),
        (
            "acted_in",
            """
            UNWIND $rows AS row
            MATCH (a:Actor {id: row.actor}), (m:Movie {id: row.movie})
            MERGE (a)-[:ACTED_IN]->(m)
            """,
        ),
        (
            "directed",
            """
            UNWIND $rows AS row
            MATCH (d:Director {id: row.director}), (m:Movie {id: row.movie})
            MERGE (d)-[:DIRECTED]->(m)
            """,
        ),
        (
            "watched",
            """
            UNWIND $rows AS row
            MATCH (u:User {id: row.user}), (m:Movie {id: row.movie})
            MERGE (u)-[r:WATCHED]->(m)
            SET r.rating = row.rating
            """,
        ),
    ]

    for key, query in queries:
        rows = data[key]
        for start in range(0, len(rows), BATCH_SIZE):
            tx.run(query, rows=rows[start:start + BATCH_SIZE]).consume()


def load_into_cognodb(data):
    missing = [
        name for name, value in {
            "COGNODB_URI": COGNODB_URI,
            "COGNODB_USER": COGNODB_USER,
            "COGNODB_PASSWORD/COGNODB_PASS": COGNODB_PASSWORD,
        }.items() if not value
    ]
    if missing:
        raise RuntimeError(
            "Missing CognoDB configuration: " + ", ".join(missing) +
            ". Set these values in .env.local."
        )

    print("Connecting to CognoDB...")
    driver = GraphDatabase.driver(COGNODB_URI, auth=(COGNODB_USER, COGNODB_PASSWORD))
    try:
        driver.verify_connectivity()
        print("CognoDB connection verified.")
        with driver.session() as session:
            session.execute_write(seed_database, data)
        print("Seeding complete.")
    finally:
        driver.close()


# ---------------------------------------------------------------------------
# MAIN
# ---------------------------------------------------------------------------

def main():
    movies_filtered, credits_filtered, ratings_final, movieid_to_tmdb = load_filtered_data()
    data = build_graph_records(movies_filtered, credits_filtered, ratings_final, movieid_to_tmdb)
    load_into_cognodb(data)


if __name__ == "__main__":
    main()

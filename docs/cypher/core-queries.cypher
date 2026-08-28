// Nodenex Phase 1 query workbook
// Run each query independently in Neo4j Browser.
// Parameters are declared here for Browser convenience; Laravel will supply
// them at runtime later.

// ---------------------------------------------------------------------------
// Indexes used by the anchor lookups and relationship joins
// ---------------------------------------------------------------------------

CREATE INDEX movie_id_index IF NOT EXISTS FOR (m:Movie) ON (m.id);
CREATE INDEX movie_title_index IF NOT EXISTS FOR (m:Movie) ON (m.title);
CREATE INDEX actor_id_index IF NOT EXISTS FOR (a:Actor) ON (a.id);
CREATE INDEX actor_name_index IF NOT EXISTS FOR (a:Actor) ON (a.name);
CREATE INDEX director_id_index IF NOT EXISTS FOR (d:Director) ON (d.id);
CREATE INDEX user_id_index IF NOT EXISTS FOR (u:User) ON (u.id);

// ---------------------------------------------------------------------------
// Browse: genres and a small sample for the pick-a-movie screen
// ---------------------------------------------------------------------------

MATCH (m:Movie)
RETURN m.genre AS genre, collect(m.title)[0..10] AS sample
ORDER BY genre;

// Optional genre filter. Omit $genre or pass null to show every genre.
MATCH (m:Movie)
WHERE $genre IS NULL OR m.genre = $genre
RETURN m.id AS id, m.title AS title, m.year AS year, m.genre AS genre
ORDER BY title
LIMIT $limit;

// ---------------------------------------------------------------------------
// Movie recommendations: primary network paths
// ---------------------------------------------------------------------------

// Parameters: $movieTitle
MATCH (seed:Movie {title: $movieTitle})
MATCH p=(seed)-[:ACTED_IN|DIRECTED*2..6]-(candidate:Movie)
WHERE candidate <> seed
WITH candidate, p, nodes(p)[1].name AS connectorName
WITH candidate,
     min(length(p)) AS distance,
     count(p) AS pathCount,
     collect(DISTINCT connectorName)[0] AS connectorName
RETURN candidate.title AS title,
       distance,
       pathCount,
       (1.0 / toFloat(distance)) * log10(1.0 + toFloat(pathCount)) AS relevanceScore,
       connectorName
ORDER BY relevanceScore DESC, pathCount DESC, distance ASC, title
LIMIT $limit;

// ---------------------------------------------------------------------------
// Movie recommendations: distinct secondary section
// ---------------------------------------------------------------------------

// Actor-only traversal at 3–6 hops ensures this section stays focused on
// actor networks while remaining distinct from the primary mixed network.
// Parameters: $movieTitle
MATCH (seed:Movie {title: 'Forrest Gump'})

OPTIONAL MATCH (seed)<-[:ACTED_IN]-(a:Actor)-[:ACTED_IN]->(candidate:Movie)
WHERE candidate <> seed
WITH seed, candidate, count(DISTINCT a) AS sharedActors

OPTIONAL MATCH (seed)<-[:DIRECTED]-(d:Director)-[:DIRECTED]->(candidate)
WITH seed, candidate, sharedActors, count(DISTINCT d) AS sharedDirectors

OPTIONAL MATCH (seed)-[:IN_GENRE]->(g:Genre)<-[:IN_GENRE]-(candidate)
WITH candidate,
     sharedActors,
     sharedDirectors,
     count(DISTINCT g) AS sharedGenres

WHERE candidate IS NOT NULL

RETURN
    candidate.title AS title,
    sharedActors,
    sharedDirectors,
    sharedGenres,
    (
        sharedActors * 0.4 +
        sharedDirectors * 0.3 +
        sharedGenres * 0.3
    ) AS score
ORDER BY score DESC
LIMIT 10;

// ---------------------------------------------------------------------------
// User recommendations: similar taste
// ---------------------------------------------------------------------------

// Parameters: $userId
MATCH (seed:User {id: $userId})
MATCH p=(seed)-[:WATCHED*2..6]-(other:User)
WHERE other <> seed
WITH seed, other, collect(p) AS paths
MATCH (seed)-[:WATCHED]-(seedMovie:Movie)
WITH other, paths, collect(seedMovie) AS seedMovies
MATCH (other)-[:WATCHED]-(shared:Movie)
WHERE shared IN seedMovies
WITH other, paths, count(DISTINCT shared) AS sharedMovies
RETURN other.name AS name,
       sharedMovies,
       (1.0 / min([path IN paths | length(path)])) * log10(1.0 + size(paths)) AS relevanceScore
ORDER BY relevanceScore DESC, sharedMovies DESC, name
LIMIT $limit;

// ---------------------------------------------------------------------------
// User recommendations: movies watched by people with similar taste
// ---------------------------------------------------------------------------

// Parameters: $userId
MATCH (seed:User {id: $userId})
MATCH p=(seed)-[:WATCHED*2..6]-(other:User)-[:WATCHED]-(candidate:Movie)
WHERE other <> seed
  AND NOT (seed)-[:WATCHED]-(candidate)
WITH candidate, collect(p) AS paths
RETURN candidate.title AS title,
       min([path IN paths | length(path)]) AS distance,
       size(paths) AS pathCount,
       (1.0 / min([path IN paths | length(path)])) * log10(1.0 + size(paths)) AS relevanceScore,
       'similar user' AS connectorName
ORDER BY relevanceScore DESC, pathCount DESC, distance ASC, title
LIMIT $limit;

// ---------------------------------------------------------------------------
// Profiling templates
// ---------------------------------------------------------------------------

// Prefix each final query with PROFILE in Neo4j Browser. Confirm the anchor
// operator is NodeIndexSeek for Movie.title or User.id, then record the
// Browser-reported time in docs/query-validation.md.

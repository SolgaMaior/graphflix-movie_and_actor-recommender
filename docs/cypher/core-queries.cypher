// Graphflix Phase 1 query workbook
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
WITH candidate, collect(p) AS paths
UNWIND paths AS p
WITH candidate, paths, p, nodes(p)[1] AS connector
WITH candidate,
     min(length(p)) AS distance,
     size(paths) AS pathCount,
     collect(DISTINCT connector.name)[0] AS connectorName
RETURN candidate.title AS title,
       distance,
       pathCount,
       (1.0 / distance) * log10(1.0 + pathCount) AS relevanceScore,
       connectorName
ORDER BY relevanceScore DESC, pathCount DESC, distance ASC, title
LIMIT $limit;

// ---------------------------------------------------------------------------
// Movie recommendations: distinct secondary section
// ---------------------------------------------------------------------------

// Same traversal, but distance >= 3 ensures this section does not overlap
// with the closest primary recommendations.
// Parameters: $movieTitle
MATCH (seed:Movie {title: $movieTitle})
MATCH p=(seed)-[:ACTED_IN|DIRECTED*3..6]-(candidate:Movie)
WHERE candidate <> seed
WITH candidate, collect(p) AS paths
UNWIND paths AS p
WITH candidate, paths, p, nodes(p)[1] AS connector
WITH candidate,
     min(length(p)) AS distance,
     size(paths) AS pathCount,
     collect(DISTINCT connector.name)[0] AS connectorName
RETURN candidate.title AS title,
       distance,
       pathCount,
       (1.0 / distance) * log10(1.0 + pathCount) AS relevanceScore,
       connectorName
ORDER BY relevanceScore DESC, pathCount DESC, distance ASC, title
LIMIT $limit;

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

# Graph query validation

The Phase 1 query workbook is in [`cypher/core-queries.cypher`](cypher/core-queries.cypher).

## Phase 2 parameters

The recommendation queries accept these runtime parameters:

- Movie queries: `$movieTitle`, `$limit`
- User queries: `$userId`, `$limit`
- Browse queries: `$genre` (nullable), `$limit`

The seeded graph uses movie titles as the movie anchor and IDs such as `u1` as
the user anchor. Laravel should pass values as parameters rather than
interpolating them into Cypher.

## Output contracts

Both movie recommendation sections return the same shape:

```text
{ title, distance, pathCount, relevanceScore, connectorName }
```

The similar-user section returns:

```text
{ name, sharedMovies, relevanceScore }
```

The people-like-you movie section returns the movie recommendation shape. Its
`connectorName` is `similar user`, and its `distance` includes the final
User-to-Movie hop.

## Validation checklist

- Run the browse query and confirm genres return grouped samples.
- Run both movie recommendation queries with at least four different seed
  titles. Confirm the secondary results have `distance >= 3`.
- Run both user recommendation queries with at least four different user IDs.
- Prefix each final query with `PROFILE`.
- Confirm the movie-title and user-ID anchors use `NodeIndexSeek` rather than
  a label scan.
- Record result counts and execution times here after testing against the
  seeded CognoDB graph.

## Profile results

Live profiling is pending. No query timings are recorded until the queries are
run against the seeded CognoDB instance.

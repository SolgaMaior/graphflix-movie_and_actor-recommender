<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Throwable;

final class GraphService
{
    private ?ClientInterface $client = null;

    private ?string $lastError = null;

    public function __construct(
        private readonly ?string $uri,
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly int $retries = 3,
        private readonly int $retryDelayMs = 250,
    ) {}

    /** @return list<Movie> */
    public function moviesByGenre(?string $genre = null, int $limit = 50): array
    {
        $rows = $this->run(
            <<<'CYPHER'
            MATCH (m:Movie)
            WHERE $genre IS NULL OR m.genre = $genre
            RETURN m.id AS id, m.title AS title, m.year AS year, m.genre AS genre
            ORDER BY title
            LIMIT $limit
            CYPHER,
            ['genre' => $genre, 'limit' => $this->safeLimit($limit)],
        );

        return array_map(static fn (array $row): Movie => new Movie(
            (string) ($row['id'] ?? ''),
            (string) ($row['title'] ?? 'Unknown'),
            isset($row['year']) ? (int) $row['year'] : null,
            isset($row['genre']) ? (string) $row['genre'] : null,
        ), $rows);
    }

    public function error(): ?string
    {
        return $this->lastError;
    }

    /** @return list<string> */
    public function actorsForMovie(string $title): array
    {
        $rows = $this->run(
            'MATCH (m:Movie {title: $movieTitle})<-[:ACTED_IN]-(a:Actor) RETURN a.name AS name ORDER BY a.id LIMIT 1',
            ['movieTitle' => $title],
        );

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['name'] ?? ''),
            $rows,
        )));
    }

    public function movieByTitle(string $title): ?Movie
    {
        $row = $this->run(
            'MATCH (m:Movie {title: $movieTitle}) RETURN m.id AS id, m.title AS title, m.year AS year, m.genre AS genre LIMIT 1',
            ['movieTitle' => $title],
        )[0] ?? null;

        return $row ? new Movie(
            id: (string) ($row['id'] ?? ''),
            title: (string) ($row['title'] ?? $title),
            year: isset($row['year']) ? (int) $row['year'] : null,
            genre: isset($row['genre']) ? (string) $row['genre'] : null,
        ) : null;
    }

    /** @return list<string> */
    public function directorsForMovie(string $title): array
    {
        $rows = $this->run(
            'MATCH (m:Movie {title: $movieTitle})<-[:DIRECTED]-(d:Director) RETURN d.name AS name ORDER BY name',
            ['movieTitle' => $title],
        );

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['name'] ?? ''),
            $rows,
        )));
    }

    /** @return list<Recommendation> */
    public function otherMoviesByActors(string $title, int $limit = 10): array
    {
        $rows = $this->run(
            <<<'CYPHER'
            MATCH (seed:Movie {title: $movieTitle})
            MATCH (actor:Actor)-[:ACTED_IN]->(seed)
            MATCH (actor)-[:ACTED_IN]->(candidate:Movie)

            WHERE candidate <> seed

            WITH candidate,
                count(DISTINCT actor) AS sharedActors,
                collect(DISTINCT actor.name) AS actors

            RETURN candidate.title AS title,
                sharedActors,
                actors[0] AS connectorName,
                'Actor' AS connectorType

            ORDER BY sharedActors DESC, title ASC
            LIMIT $limit
            CYPHER,
            ['movieTitle' => $title, 'limit' => $this->safeLimit($limit)],
        );

        return array_map(static fn (array $row): Recommendation => new Recommendation(
            title: (string) ($row['title'] ?? ''),
            distance: (int) ($row['distance'] ?? 3),
            pathCount: (int) ($row['pathCount'] ?? 0),
            relevanceScore: (float) ($row['relevanceScore'] ?? 0),
            connectorName: isset($row['connectorName']) ? (string) $row['connectorName'] : null,
            connectorType: isset($row['connectorType']) ? (string) $row['connectorType'] : null,
        ), $rows);
    }

    /** @return list<User> */
    public function allUsers(int $limit = 100): array
    {
        return array_map(static fn (array $row): User => new User(
            (string) ($row['id'] ?? ''),
            (string) ($row['name'] ?? 'Unknown user'),
        ), $this->run(
            'MATCH (u:User) RETURN u.id AS id, u.name AS name ORDER BY u.id LIMIT $limit',
            ['limit' => $this->safeLimit($limit)],
        ));
    }

    /** @return list<Movie> */
    public function popularMoviesByUsers(int $limit = 100): array
    {
        return array_map(static fn (array $row): Movie => new Movie(
            id: (string) ($row['id'] ?? ''),
            title: (string) ($row['title'] ?? 'Unknown'),
            year: isset($row['year']) ? (int) $row['year'] : null,
            genre: isset($row['genre']) ? (string) $row['genre'] : null,
            watchers: (int) ($row['watchers'] ?? 0),
        ), $this->run(
            <<<'CYPHER'
            MATCH (u:User)-[:WATCHED]->(m:Movie)
            WITH m, count(DISTINCT u) AS watchers
            RETURN m.id AS id, m.title AS title, m.year AS year,
                   m.genre AS genre, watchers
            ORDER BY watchers DESC, title
            LIMIT $limit
            CYPHER,
            ['limit' => $this->safeLimit($limit)],
        ));
    }

    /** @return list<Recommendation> */
    public function recommendationsForMovie(string $title, int $minDistance = 2, int $maxDistance = 6, int $limit = 10): array
    {
        $minDistance = max(1, $minDistance);
        $maxDistance = max($minDistance, $maxDistance);
        $rows = $this->run(
            <<<'CYPHER'
            MATCH (seed:Movie {title: $movieTitle})
            OPTIONAL MATCH (seed)<-[:ACTED_IN]-(a:Actor)-[:ACTED_IN]->(candidate:Movie)
            WHERE candidate <> seed
            WITH seed, candidate, count(DISTINCT a) AS sharedActors

            OPTIONAL MATCH (seed)<-[:DIRECTED]-(d:Director)-[:DIRECTED]->(candidate)
            WITH seed, candidate, sharedActors, count(DISTINCT d) AS sharedDirectors

            WHERE candidate IS NOT NULL

            RETURN
                candidate.title AS title,
                sharedActors,
                sharedDirectors,
                2 AS distance,
                (
                    sharedActors * 0.4 +
                    sharedDirectors * 0.6
                ) AS relevanceScore
            ORDER BY relevanceScore DESC
            LIMIT $limit;
            CYPHER,
            [
                'movieTitle' => $title,
                'minDistance' => $minDistance,
                'maxDistance' => $maxDistance,
                'limit' => $this->safeLimit($limit),
            ],
        );

        return array_map(static fn (array $row): Recommendation => new Recommendation(
            title: (string) ($row['title'] ?? ''),
            distance: isset($row['distance']) ? (int) $row['distance'] : null,
            pathCount: (int) ($row['pathCount'] ?? 0),
            relevanceScore: (float) ($row['relevanceScore'] ?? 0),
            connectorName: isset($row['connectorName']) ? (string) $row['connectorName'] : null,
            connectorType: isset($row['connectorType']) ? (string) $row['connectorType'] : null,
        ), $rows);
    }

    /** @return list<Recommendation> */
    public function similarUsers(string $userId, int $limit = 20): array
    {
        $rows = $this->run(
            <<<'CYPHER'
            MATCH (seed:User {id: $userId})-[:WATCHED]-(shared:Movie)-[:WATCHED]-(other:User)
            WHERE other <> seed
            WITH other, count(DISTINCT shared) AS sharedMovies
            RETURN other.name AS name, sharedMovies,
                   toFloat(sharedMovies) AS relevanceScore
            ORDER BY relevanceScore DESC, sharedMovies DESC, name
            LIMIT $limit
            CYPHER,
            ['userId' => $userId, 'limit' => $this->safeLimit($limit)],
        );

        return array_map(static fn (array $row): Recommendation => new Recommendation(
            name: (string) ($row['name'] ?? ''),
            sharedMovies: (int) ($row['sharedMovies'] ?? 0),
            relevanceScore: (float) ($row['relevanceScore'] ?? 0),
        ), $rows);
    }

    /** @return list<Recommendation> */
    public function recommendedMoviesForUser(string $userId, int $limit = 20): array
    {
        $rows = $this->run(
            <<<'CYPHER'
            MATCH (seed:User {id: $userId})-[:WATCHED]-(shared:Movie)-[:WATCHED]-(other:User)-[:WATCHED]-(candidate:Movie)
            WHERE other <> seed AND NOT (seed)-[:WATCHED]-(candidate)
            WITH candidate, count(DISTINCT other) AS pathCount
            RETURN candidate.title AS title, 3 AS distance, pathCount,
                   toFloat(pathCount) AS relevanceScore,
                   'similar user' AS connectorName
            ORDER BY relevanceScore DESC, pathCount DESC, title
            LIMIT $limit
            CYPHER,
            ['userId' => $userId, 'limit' => $this->safeLimit($limit)],
        );

        return array_map(static fn (array $row): Recommendation => new Recommendation(
            title: (string) ($row['title'] ?? ''),
            distance: (int) ($row['distance'] ?? 3),
            pathCount: (int) ($row['pathCount'] ?? 0),
            relevanceScore: (float) ($row['relevanceScore'] ?? 0),
            connectorName: (string) ($row['connectorName'] ?? 'similar user'),
        ), $rows);
    }

    /** @return list<array<string, mixed>> */
    private function run(string $query, array $parameters): array
    {
        $attempts = min(max($this->retries, 1), 5);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $result = $this->client()->run($query, $parameters);
                $this->lastError = null;

                return array_map(static fn ($row): array => $row->toArray(), $result->getResults()->toArray());
            } catch (Throwable $exception) {
                $this->closeClient();

                if ($attempt === $attempts) {
                    Log::error('CognoDB query failed after retries', [
                        'attempts' => $attempts,
                        'message' => $exception->getMessage(),
                    ]);
                    $this->lastError = 'CognoDB is unavailable. Check the connection settings and network access.';

                    return [];
                }

                Log::warning('CognoDB query failed; retrying', [
                    'attempt' => $attempt,
                    'attempts' => $attempts,
                    'message' => $exception->getMessage(),
                ]);
                usleep(max($this->retryDelayMs, 0) * 1000 * $attempt);
            }
        }

        return [];
    }

    private function client(): ClientInterface
    {
        if ($this->client === null) {
            if (! $this->uri || ! $this->username || ! $this->password) {
                throw new \RuntimeException('CognoDB credentials are not configured.');
            }

            $this->client = ClientBuilder::create()
                ->withDriver('bolt', $this->uri, Authenticate::basic($this->username, $this->password))
                ->withDefaultDriver('bolt')
                ->build();

            $this->client->getDriver('bolt')->verifyConnectivity();
        }

        return $this->client;
    }

    private function closeClient(): void
    {
        if ($this->client !== null) {
            $this->client->getDriver('bolt')->closeConnections();
            $this->client = null;
        }
    }

    private function safeLimit(int $limit): int
    {
        return min(max($limit, 1), 100);
    }
}

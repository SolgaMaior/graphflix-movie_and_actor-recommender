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

    public function __construct(
        private readonly ?string $uri,
        private readonly ?string $username,
        private readonly ?string $password,
    ) {}

    /** @return list<Movie> */
    public function moviesByGenre(?string $genre = null, int $limit = 100): array
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

    /** @return list<Recommendation> */
    public function recommendationsForMovie(string $title, int $minDistance = 2, int $maxDistance = 6, int $limit = 20): array
    {
        $minDistance = max(1, $minDistance);
        $maxDistance = max($minDistance, $maxDistance);
        $rows = $this->run(
            <<<'CYPHER'
            MATCH (seed:Movie {title: $movieTitle})
            MATCH p=(seed)-[:ACTED_IN|DIRECTED*2..6]-(candidate:Movie)
            WHERE candidate <> seed
              AND length(p) >= $minDistance
              AND length(p) <= $maxDistance
            WITH candidate, collect(p) AS paths
            WITH candidate,
                 min([path IN paths | length(path)]) AS distance,
                 size(paths) AS pathCount,
                 collect(DISTINCT nodes(paths[0])[1].name)[0] AS connectorName
            RETURN candidate.title AS title, distance, pathCount,
                   (1.0 / distance) * log10(1.0 + pathCount) AS relevanceScore,
                   connectorName
            ORDER BY relevanceScore DESC, pathCount DESC, distance ASC, title
            LIMIT $limit
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
        try {
            $result = $this->client()->run($query, $parameters);

            return array_map(static fn ($row): array => $row->toArray(), $result->getResults()->toArray());
        } catch (Throwable $exception) {
            Log::error('CognoDB query failed', ['message' => $exception->getMessage()]);

            return [];
        }
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
        }

        return $this->client;
    }

    private function safeLimit(int $limit): int
    {
        return min(max($limit, 1), 100);
    }
}

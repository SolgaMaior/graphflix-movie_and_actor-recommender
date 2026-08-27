<?php

namespace App\Models;

final readonly class Recommendation implements \JsonSerializable
{
    public function __construct(
        public ?string $title = null,
        public ?string $name = null,
        public ?int $distance = null,
        public int $pathCount = 0,
        public int $sharedMovies = 0,
        public float $relevanceScore = 0.0,
        public ?string $connectorName = null,
        public ?string $connectorType = null,
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this), static fn ($value) => $value !== null);
    }
}

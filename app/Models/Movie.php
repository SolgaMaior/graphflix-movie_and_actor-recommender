<?php

namespace App\Models;

final readonly class Movie implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $title,
        public ?int $year = null,
        public ?string $genre = null,
    ) {}

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

<?php

namespace App\Models;

final readonly class User implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

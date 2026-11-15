<?php

namespace App\Domain;

final readonly class Category
{
    public function __construct(public string $id, public string $name)
    {
    }
}

<?php

namespace App\Application\Ports;

use App\Domain\Exceptions\ValidationFailed;

/** Contract §1: `page` starts at 1, `size` defaults to 20 and never exceeds 50. */
final readonly class PageRequest
{
    public const DEFAULT_SIZE = 20;

    public const MAX_SIZE = 50;

    private function __construct(public int $page, public int $size)
    {
    }

    public static function of(int $page = 1, int $size = self::DEFAULT_SIZE): self
    {
        $details = [];

        if ($page < 1) {
            $details[] = ['field' => 'page', 'issue' => 'debe ser mayor o igual a 1'];
        }

        if ($size < 1 || $size > self::MAX_SIZE) {
            $details[] = ['field' => 'size', 'issue' => 'entre 1 y '.self::MAX_SIZE];
        }

        if ($details !== []) {
            throw ValidationFailed::fields($details);
        }

        return new self($page, $size);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->size;
    }
}

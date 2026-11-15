<?php

namespace App\Domain\Exceptions;

final class ModelCodeTaken extends DomainError
{
    public const CODE = 'MODEL_CODE_TAKEN';

    public static function create(string $modelCode): self
    {
        return new self("Ya hay un portátil activo con el código de modelo «$modelCode».", [['field' => 'model_code', 'issue' => 'repetido']]);
    }
}

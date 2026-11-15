<?php

namespace App\Domain;

enum Role: string
{
    case Admin = 'admin';
    case Assistant = 'assistant';
}

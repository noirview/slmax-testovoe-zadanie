<?php

namespace App\Enums;

enum Condition: string
{
    case OVER = '>';
    case LESS = '<';
    case EQUAL = '=';
}
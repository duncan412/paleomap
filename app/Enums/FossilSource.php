<?php

declare(strict_types=1);

namespace App\Enums;

enum FossilSource: string
{
    case Naturalis = 'naturalis';
    case Gbif = 'gbif';
}

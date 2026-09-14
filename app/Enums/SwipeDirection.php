<?php

declare(strict_types=1);

namespace App\Enums;

enum SwipeDirection: string
{
    case Like = 'like';
    case Dislike = 'dislike';
}

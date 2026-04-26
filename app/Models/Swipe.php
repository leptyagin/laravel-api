<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id_1',
    'user_id_2',
    'user_like_1',
    'user_like_2',
    'matched_at',
])]
final class Swipe extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_like_1' => 'boolean',
            'user_like_2' => 'boolean',
            'matched_at' => 'datetime',
        ];
    }
}

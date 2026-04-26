<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'birth_date',
    'gender',
    'city',
    'bio',
    'status',
])]
final class Profile extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'city' => City::class,
            'status' => Status::class,
        ];
    }
}

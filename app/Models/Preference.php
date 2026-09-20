<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property Gender $gender
 * @property int $min_age
 * @property int $max_age
 */
#[Fillable([
    'user_id',
    'gender',
    'min_age',
    'max_age',
])]
final class Preference extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'min_age' => 'integer',
            'max_age' => 'integer',
        ];
    }
}

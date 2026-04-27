<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PhotoDTO;
use App\Models\Photo;
use App\Models\User;

final class PhotoService
{
    public function upload(User $user, PhotoDTO $dto): Photo
    {
        $path = $dto->file->store('photos', 'public');

        return Photo::query()->create([
            'user_id' => $user->id,
            'path' => $path,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PhotoStorageInterface;
use App\DTOs\PhotoDTO;
use App\Models\Photo;
use App\Models\User;
use App\ValueObjects\FileData;
use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class PhotoService
{
    public function __construct(
        private PhotoStorageInterface $storage,
        private DatabaseManager $db,
    ) {}

    public function upload(User $user, PhotoDTO $dto): Photo
    {
        $fileData = FileData::fromUploadedFile($dto->file);

        $stored = $this->storage->store($fileData);

        try {
            return $this->db->transaction(fn () => Photo::query()->create([
                'user_id' => $user->id,
                'path' => $stored->path,
            ]));
        } catch (Throwable $throwable) {
            $this->storage->delete($stored->path);
            throw $throwable;
        }
    }
}

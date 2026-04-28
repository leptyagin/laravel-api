<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PhotoStorageInterface;
use App\ValueObjects\FileData;
use App\ValueObjects\StoredFile;
use DomainException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Throwable;

final readonly class LocalPhotoStorage implements PhotoStorageInterface
{
    public function __construct(
        private FilesystemFactory $filesystem,
        private string $disk,
        private string $directory,
    ) {}

    public function store(FileData $file): StoredFile
    {
        $filename = uniqid().'_'.$file->originalName;

        $path = $this->filesystem
            ->disk($this->disk)
            ->put($this->directory.'/'.$filename, $file->contents);

        throw_unless($path, DomainException::class, 'Failed to store file');

        return new StoredFile(
            path: $this->directory.'/'.$filename,
            url: $this->filesystem->disk($this->disk)->url($this->directory.'/'.$filename)
        );
    }

    public function delete(string $path): void
    {
        try {
            $this->filesystem->disk($this->disk)->delete($path);
        } catch (Throwable $throwable) {
            logger()->warning('Failed to delete file', [
                'path' => $path,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}

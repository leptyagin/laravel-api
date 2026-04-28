<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Http\UploadedFile;

final readonly class FileData
{
    public function __construct(
        public string $contents,
        public string $originalName,
        public string $mimeType,
    ) {}

    public static function fromUploadedFile(UploadedFile $file): self
    {
        return new self(
            contents: file_get_contents($file->getRealPath()),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType(),
        );
    }
}

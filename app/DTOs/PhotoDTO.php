<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class PhotoDTO
{
    public function __construct(
        public UploadedFile $file,
    ) {}
}

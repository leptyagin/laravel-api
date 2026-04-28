<?php

declare(strict_types=1);

namespace App\Contracts;

use App\ValueObjects\FileData;
use App\ValueObjects\StoredFile;
use DomainException;

interface PhotoStorageInterface
{
    /**
     * @throws DomainException
     */
    public function store(FileData $file): StoredFile;

    public function delete(string $path): void;
}

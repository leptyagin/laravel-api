<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProfileCacheServiceInterface;
use App\DTOs\ProfileDTO;
use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\Models\User;
use App\ValueObjects\Age;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class ProfileCacheService implements ProfileCacheServiceInterface
{
    private const string PREFIX = 'user_profile:';

    public function __construct(
        private CacheRepository $cache,
        private ProfileQueryService $query,
        private ProfileMapper $mapper,
    ) {}

    public function get(int $userId): ProfileDTO
    {
        $data = $this->cache->remember(
            $this->key($userId),
            60 * 60 * 10,
            function () use ($userId): array {
                $user = $this->query->getById($userId);

                return $this->toArray($user);
            }
        );

        return $this->fromArray($data);
    }

    public function invalidate(int $userId): void
    {
        $this->cache->forget($this->key($userId));
    }

    private function key(int $userId): string
    {
        return self::PREFIX.$userId;
    }

    private function toArray(User $user): array
    {
        return $this->mapper->map($user)->toArray();
    }

    private function fromArray(array $data): ProfileDTO
    {
        return new ProfileDTO(
            id: $data['id'],
            name: $data['name'],
            age: new Age($data['age']),
            city: City::from($data['city']),
            bio: $data['bio'],
            status: Status::from($data['status']),
            photo: $data['photo'],
            gender: Gender::from($data['gender']),
            lookingFor: Gender::from($data['lookingFor']),
            partnerMaxAge: new Age($data['partnerMaxAge']),
            partnerMinAge: new Age($data['partnerMinAge']),
        );
    }
}

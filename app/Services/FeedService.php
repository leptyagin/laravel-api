<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FeedCacheServiceInterface;
use App\Contracts\ProfileCacheServiceInterface;
use App\DTOs\FeedPageDTO;
use App\DTOs\FeedPageParamsDTO;
use App\DTOs\ProfileDTO;
use App\Enums\Status;
use App\Exceptions\IncompleteProfileException;
use App\Exceptions\ProfileNotActiveException;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class FeedService
{
    public function __construct(
        private FeedCacheServiceInterface $feedCache,
        private FeedQueryService $query,
        private ProfileCacheServiceInterface $profileCache,
    ) {}

    public function getPage(User $user, FeedPageParamsDTO $params): FeedPageDTO
    {
        $this->assertCanViewFeed($user);

        $ids = $this->feedCache->remember(
            $user->id,
            fn (): array => $this->query->candidateIdsFor($user, (int) config('feed.pool_size')),
        );

        $remaining = $params->cursor === null
            ? $ids
            : array_values(array_filter($ids, fn (int $id): bool => $id < $params->cursor));

        $pageIds = array_slice($remaining, 0, $params->limit);
        $nextCursor = count($remaining) > $params->limit ? $pageIds[array_key_last($pageIds)] : null;

        return new FeedPageDTO(
            items: $this->loadProfiles($user, $pageIds),
            nextCursor: $nextCursor,
        );
    }

    private function assertCanViewFeed(User $user): void
    {
        $profile = $user->profile;

        throw_if($profile === null, IncompleteProfileException::class);
        throw_if($profile->status !== Status::Active, ProfileNotActiveException::class);
    }

    /**
     * @param  list<int> $candidateIds
     * @return list<ProfileDTO>
     */
    private function loadProfiles(User $viewer, array $candidateIds): array
    {
        $profiles = [];

        foreach ($candidateIds as $candidateId) {
            try {
                $profile = $this->profileCache->get($candidateId);
            } catch (ModelNotFoundException|DomainException $exception) {
                logger()->warning('Skipping broken feed candidate', [
                    'viewer_id' => $viewer->id,
                    'candidate_id' => $candidateId,
                    'error' => $exception->getMessage(),
                ]);
                $this->feedCache->removeCandidate($viewer->id, $candidateId);

                continue;
            }

            if ($profile->status !== Status::Active) {
                $this->feedCache->removeCandidate($viewer->id, $candidateId);

                continue;
            }

            $profiles[] = $profile;
        }

        return $profiles;
    }
}

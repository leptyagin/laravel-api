<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\SwipeResultDTO;
use App\Enums\SwipeDirection;
use App\Events\UsersMatched;
use App\Exceptions\AlreadySwipedException;
use App\Exceptions\CannotSwipeSelfException;
use App\Models\Swipe;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;

final readonly class SwipeService
{
    private const int MAX_INSERT_RETRIES = 3;

    public function __construct(
        private DatabaseManager $db,
    ) {}

    public function swipe(User $actor, User $target, SwipeDirection $direction): SwipeResultDTO
    {
        throw_if($actor->id === $target->id, CannotSwipeSelfException::class);

        $result = $this->attemptSwipeWithRetry($actor, $target, $direction);

        if ($result->matched) {
            UsersMatched::dispatch($result->swipe);
        }

        return $result;
    }

    private function attemptSwipeWithRetry(User $actor, User $target, SwipeDirection $direction): SwipeResultDTO
    {
        for ($attempt = 1; $attempt <= self::MAX_INSERT_RETRIES; $attempt++) {
            try {
                return $this->db->transaction(
                    fn(): SwipeResultDTO => $this->processSwipe($actor, $target, $direction)
                );
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        throw new RuntimeException('Could not process swipe after retries, please try again.');
    }

    private function processSwipe(User $actor, User $target, SwipeDirection $direction): SwipeResultDTO
    {
        $isActorFirst = $actor->id < $target->id;
        $userId1 = $isActorFirst ? $actor->id : $target->id;
        $userId2 = $isActorFirst ? $target->id : $actor->id;
        $liked = $direction === SwipeDirection::Like;

        $swipe = Swipe::query()
            ->where('user_id_1', $userId1)
            ->where('user_id_2', $userId2)
            ->lockForUpdate()
            ->first();

        if ($swipe === null) {
            $swipe = Swipe::query()->create([
                'user_id_1' => $userId1,
                'user_id_2' => $userId2,
                'user_like_1' => $isActorFirst ? $liked : null,
                'user_like_2' => $isActorFirst ? null : $liked,
            ]);

            return new SwipeResultDTO(matched: false, swipe: $swipe);
        }

        $actorAlreadySwiped = $isActorFirst
            ? $swipe->user_like_1 !== null
            : $swipe->user_like_2 !== null;

        throw_if($actorAlreadySwiped, AlreadySwipedException::class);

        $otherAlreadyLiked = $isActorFirst ? $swipe->user_like_2 : $swipe->user_like_1;
        $matched = $liked && $otherAlreadyLiked === true;

        $swipe->update([
            ...($isActorFirst ? ['user_like_1' => $liked] : ['user_like_2' => $liked]),
            ...($matched ? ['matched_at' => now()] : []),
        ]);

        return new SwipeResultDTO(matched: $matched, swipe: $swipe->fresh());
    }
}

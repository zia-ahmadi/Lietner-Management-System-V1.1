<?php

namespace App\Services;

use App\Models\Card;
use Carbon\CarbonInterface;

class LeitnerScheduler
{
    public function applyReview(Card $card, string $result, CarbonInterface $reviewedAt): Card
    {
        $boxBefore = $card->box;

        if ($result === 'correct') {
            $newBox = min($boxBefore + 1, $this->maxBox());
            $nextReviewAt = $reviewedAt->copy()->addDays($this->intervalForBox($newBox));
            $newStreak = $card->review_streak + 1;
        } else {
            $newBox = 1;
            $nextReviewAt = $reviewedAt->copy()->addDays((int) config('leitner.failed_delay_days', 1));
            $newStreak = 0;
        }

        $card->forceFill([
            'box' => $newBox,
            'review_streak' => $newStreak,
            'last_reviewed_at' => $reviewedAt,
            'next_review_at' => $nextReviewAt,
        ])->save();

        return $card->refresh();
    }

    /**
     * @return array<int, int>
     */
    public function intervals(): array
    {
        /** @var array<int, int> $intervals */
        $intervals = config('leitner.intervals', [1 => 1, 2 => 2, 3 => 4, 4 => 7, 5 => 16]);

        return $intervals;
    }

    public function maxBox(): int
    {
        $keys = array_keys($this->intervals());

        return (int) max($keys);
    }

    public function intervalForBox(int $box): int
    {
        $intervals = $this->intervals();

        return $intervals[$box] ?? end($intervals) ?: 1;
    }
}

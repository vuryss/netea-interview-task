<?php

declare(strict_types=1);

namespace App\Learning;

use DateTimeImmutable;

class Assignment
{
    private const STATUS_ON_TRACK = 'on track';
    private const STATUS_NOT_ON_TRACK = 'not on track';
    private const STATUS_OVERDUE = 'overdue';

    private DateTimeImmutable $endDate;
    private int $durationInDays;

    public function __construct(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        private readonly LearningContent $learningContent
    ) {
        $this->endDate = $endDate->setTime(hour: 23, minute: 59, second: 59);
        $this->durationInDays = $startDate->setTime(hour: 0, minute: 0)->diff($this->endDate)->days + 1;
    }

    public function generateReportForCurrentProgress(int $currentProgressPercentage): array
    {
        $now = new DateTimeImmutable();

        $endDate = $this->endDate->setTime(hour: 23, minute: 59, second: 59);

        $daysLeft = (int) $now->diff($endDate)->format('%r%a') + 1;

        $expectedProgressAsPercent = $this->getExpectedProgressPercentage($daysLeft);
        $neededDailyLearningTime = $this->getNeededDailyLearningTime($daysLeft, $currentProgressPercentage);
        $status = $this->determineProgressStatus($daysLeft, $currentProgressPercentage, $expectedProgressAsPercent);

        return [
            'progress_status' => $status,
            'expected_progress' => $expectedProgressAsPercent,
            'needed_daily_learning_time' => $neededDailyLearningTime,
        ];
    }

    private function getExpectedProgressPercentage(int $daysLeft): int
    {
        if ($daysLeft < 1) {
            return 100;
        }

        if ($daysLeft > $this->durationInDays) {
            return 0;
        }

        $expectedDailyProgressInSeconds = (int) ceil(
            $this->learningContent->getDurationInSeconds() / $this->durationInDays
        );
        $expectedProgressInSeconds = ($this->durationInDays - $daysLeft + 1) * $expectedDailyProgressInSeconds;

        return (int) ceil($expectedProgressInSeconds * 100 / $this->learningContent->getDurationInSeconds());
    }

    private function getNeededDailyLearningTime(int $daysLeft, int $currentProgressPercentage): ?int
    {
        if ($daysLeft < 1) {
            return null;
        }

        if ($daysLeft > $this->durationInDays) {
            return (int) ceil($this->learningContent->getDurationInSeconds() / $this->durationInDays);
        }

        $percentageLearningLeft = 100 - $currentProgressPercentage;
        $contentDurationLeft = (int) ceil(
            $percentageLearningLeft / 100 * $this->learningContent->getDurationInSeconds()
        );
        $remainingDaysForLearning = $daysLeft;

        return (int) ceil($contentDurationLeft / $remainingDaysForLearning);
    }

    private function determineProgressStatus(
        int $daysLeft,
        int $currentProgressPercentage,
        int $expectedProgressAsPercent
    ): string {
        if ($daysLeft < 1 && $currentProgressPercentage < 100) {
            return self::STATUS_OVERDUE;
        }

        if ($expectedProgressAsPercent > $currentProgressPercentage) {
            return self::STATUS_NOT_ON_TRACK;
        }

        return self::STATUS_ON_TRACK;
    }
}

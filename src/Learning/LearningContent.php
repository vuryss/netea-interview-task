<?php

declare(strict_types=1);

namespace App\Learning;

class LearningContent
{
    public function __construct(
        private readonly int $durationInSeconds
    ) {
    }

    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }
}

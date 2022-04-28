<?php

declare(strict_types=1);

namespace App\API\AssignmentProgressStatus;

use Symfony\Component\Serializer\Annotation\SerializedName;

class StatusReportResponse
{
    #[SerializedName('progress_status')]
    public string $progressStatus;

    #[SerializedName('expected_progress')]
    public int $expectedProgress;

    #[SerializedName('needed_daily_learning_time')]
    public ?int $neededDailyLearningTime;
}

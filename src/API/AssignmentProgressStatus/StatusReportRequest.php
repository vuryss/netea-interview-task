<?php

declare(strict_types=1);

namespace App\API\AssignmentProgressStatus;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Callback([StatusReportRequest::class, 'validateAssignmentPeriodFitsDuration'])]
#[Callback([StatusReportRequest::class, 'validateNotStartedAssignmentHasNoProgress'])]
class StatusReportRequest
{
    #[NotBlank]
    #[Range(min: 1, max: 2 ** 32)]
    public int $duration;

    #[NotBlank]
    #[Range(min: 0, max: 100)]
    public int $progressPercentage;

    #[NotBlank]
    #[Context([DateTimeNormalizer::FORMAT_KEY => DateTimeInterface::RFC3339])]
    public DateTimeImmutable $startDate;

    #[NotBlank]
    #[GreaterThan(propertyPath: 'startDate')]
    #[Context([DateTimeNormalizer::FORMAT_KEY => DateTimeInterface::RFC3339])]
    public DateTimeImmutable $endDate;

    public static function validateAssignmentPeriodFitsDuration($payload, ExecutionContextInterface $context): void
    {
        if ($payload->startDate->add(new DateInterval('PT' . $payload->duration . 'S')) > $payload->endDate) {
            $context
                ->buildViolation('Assignment period is not enough to cover the whole duration')
                ->addViolation();
        }
    }

    public static function validateNotStartedAssignmentHasNoProgress($payload, ExecutionContextInterface $context): void
    {
        $now = new DateTimeImmutable();

        if ($payload->startDate > $now && $payload->progressPercentage > 0) {
            $context
                ->buildViolation('Cannot have progress on assignment that has not started yet.')
                ->addViolation();
        }
    }
}

<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\ApiTester;
use Codeception\Example;
use Codeception\Util\HttpCode;
use DateTimeImmutable;
use DateTimeInterface;

class AssignmentProgressStatusCest
{
    protected function progressStatusDataProvider(): array
    {
        return [
            'exactly on track' => [
                'start' => '-3 days',
                'end' => '+6 days',
                'duration' => 1000,
                'progress' => 40,
                'expectedProgressStatus' => 'on track',
                'expectedProgress' => 40,
                'expectedNeededDailyLearningTime' => 86,
            ],
            'finished early' => [
                'start' => '-3 days',
                'end' => '+6 days',
                'duration' => 1000,
                'progress' => 100,
                'expectedProgressStatus' => 'on track',
                'expectedProgress' => 40,
                'expectedNeededDailyLearningTime' => 0,
            ],
            'lagging behind' => [
                'start' => '-3 days',
                'end' => '+6 days',
                'duration' => 1000,
                'progress' => 20,
                'expectedProgressStatus' => 'not on track',
                'expectedProgress' => 40,
                'expectedNeededDailyLearningTime' => 115,
            ],
            'haven\'t started at all' => [
                'start' => '-3 days',
                'end' => '+6 days',
                'duration' => 1000,
                'progress' => 0,
                'expectedProgressStatus' => 'not on track',
                'expectedProgress' => 40,
                'expectedNeededDailyLearningTime' => 143,
            ],
            'overdue' => [
                'start' => '-10 days',
                'end' => '-5 days',
                'duration' => 1000,
                'progress' => 20,
                'expectedProgressStatus' => 'overdue',
                'expectedProgress' => 100,
                'expectedNeededDailyLearningTime' => null,
            ],
            'future assignment' => [
                'start' => '+5 days',
                'end' => '+10 days',
                'duration' => 1000,
                'progress' => 0,
                'expectedProgressStatus' => 'on track',
                'expectedProgress' => 0,
                'expectedNeededDailyLearningTime' => 167,
            ],
        ];
    }

    /**
     * @dataProvider progressStatusDataProvider
     */
    public function correctProgressStatus(ApiTester $I, Example $data): void
    {
        $startDate = new DateTimeImmutable($data['start']);
        $endDate = new DateTimeImmutable($data['end']);

        $I->sendGet(sprintf(
            '/assignment/status-report/%d/%d/%s/%s',
            $data['duration'],
            $data['progress'],
            $startDate->format(format: DateTimeInterface::RFC3339),
            $endDate->format(format: DateTimeInterface::RFC3339),
        ));

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseIsJson();

        $I->seeResponseContainsJson([
            'progress_status' => $data['expectedProgressStatus'],
            'expected_progress' => $data['expectedProgress'],
            'needed_daily_learning_time' => $data['expectedNeededDailyLearningTime'],
        ]);
    }

    public function endDateCannotBeBeforeStartDate(ApiTester $I): void
    {
        $startDate = new DateTimeImmutable('-3 days');
        $endDate = new DateTimeImmutable('-4 days');

        $I->sendGet(sprintf(
            '/assignment/status-report/%d/%d/%s/%s',
            1000,
            10,
            $startDate->format(DateTimeInterface::RFC3339),
            $endDate->format(DateTimeInterface::RFC3339),
        ));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->canSeeResponseIsJson();
    }

    public function assignmentDateRangeShouldNotBeLessThanVideoDuration(ApiTester $I): void
    {
        $startDate = new DateTimeImmutable('-1 days');
        $endDate = new DateTimeImmutable('+1 days');

        $I->sendGet(sprintf(
            '/assignment/status-report/%d/%d/%s/%s',
            86400 * 4,
            10,
            $startDate->format(DateTimeInterface::RFC3339),
            $endDate->format(DateTimeInterface::RFC3339),
        ));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->canSeeResponseIsJson();
    }

    public function cannotHaveProgressOnAssignmentThatHasNotStartedYet(ApiTester $I): void
    {
        $startDate = new DateTimeImmutable('+3 days');
        $endDate = new DateTimeImmutable('+4 days');

        $I->sendGet(sprintf(
            '/assignment/status-report/%d/%d/%s/%s',
            1000,
            10,
            $startDate->format(DateTimeInterface::RFC3339),
            $endDate->format(DateTimeInterface::RFC3339),
        ));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->canSeeResponseIsJson();
    }

    protected function progressPercentageDataProvider(): array
    {
        return [
            'valid 1' => [
                'percentage' => 0,
                'responseCode' => HttpCode::OK,
            ],
            'valid 2' => [
                'percentage' => 1,
                'responseCode' => HttpCode::OK,
            ],
            'valid 3' => [
                'percentage' => 50,
                'responseCode' => HttpCode::OK,
            ],
            'valid 4' => [
                'percentage' => 99,
                'responseCode' => HttpCode::OK,
            ],
            'valid 5' => [
                'percentage' => 100,
                'responseCode' => HttpCode::OK,
            ],
            'invalid 1' => [
                'percentage' => 101,
                'responseCode' => HttpCode::BAD_REQUEST,
            ],
            'invalid 2' => [
                'percentage' => -1,
                'responseCode' => HttpCode::NOT_FOUND,
            ],
            'invalid 3' => [
                'percentage' => '0.0',
                'responseCode' => HttpCode::NOT_FOUND,
            ],
            'invalid 4' => [
                'percentage' => 50.3,
                'responseCode' => HttpCode::NOT_FOUND,
            ],

        ];
    }

    /**
     * @dataProvider progressPercentageDataProvider
     */
    public function correctWholeNumberShouldBeGivenAsProgressPercentage(ApiTester $I, Example $data): void
    {
        $startDate = new DateTimeImmutable('-3 days');
        $endDate = new DateTimeImmutable('+5 days');

        $I->sendGet(sprintf(
            '/assignment/status-report/%s/%s/%s/%s',
            1000,
            $data['percentage'],
            $startDate->format(format: DateTimeInterface::RFC3339),
            $endDate->format(format: DateTimeInterface::RFC3339),
        ));

        $I->seeResponseCodeIs($data['responseCode']);
    }

    protected function durationDataProvider(): array
    {
        return [
            'valid 1' => [
                'duration' => 100,
                'responseCode' => HttpCode::OK,
            ],
            'valid 2' => [
                'duration' => 1,
                'responseCode' => HttpCode::OK,
            ],
            'valid 3' => [
                'duration' => 15000,
                'responseCode' => HttpCode::OK,
            ],
            'valid 4' => [
                'duration' => 1000000,
                'responseCode' => HttpCode::OK,
            ],
            'invalid 1' => [
                'duration' => -101,
                'responseCode' => HttpCode::NOT_FOUND,
            ],
            'invalid 2' => [
                'duration' => 50.20,
                'responseCode' => HttpCode::NOT_FOUND,
            ],
            'invalid 3' => [
                'duration' => '0.0',
                'responseCode' => HttpCode::NOT_FOUND,
            ],
            'invalid 4' => [
                'duration' => -40.35,
                'responseCode' => HttpCode::NOT_FOUND,
            ],
            'invalid 5' => [
                'duration' => 'string',
                'responseCode' => HttpCode::NOT_FOUND,
            ],
            'invalid 6' => [
                'duration' => '',
                'responseCode' => HttpCode::NOT_FOUND,
            ],
        ];
    }

    /**
     * @dataProvider durationDataProvider
     */
    public function positiveWholeNumberShouldBeGivenAsDuration(ApiTester $I, Example $data): void
    {
        $startDate = new DateTimeImmutable('-3 days');
        $endDate = new DateTimeImmutable('+50 days');

        $I->sendGet(sprintf(
            '/assignment/status-report/%s/%s/%s/%s',
            $data['duration'],
            10,
            $startDate->format(format: DateTimeInterface::RFC3339),
            $endDate->format(format: DateTimeInterface::RFC3339),
        ));

        $I->seeResponseCodeIs($data['responseCode']);
    }

    protected function incorrectParametersDataProvider(): array
    {
        $date = new DateTimeImmutable('now');

        return [
            'invalid 1' => [
                'startDate' => $date->format('Y-m-d'),
                'endDate' => $date->add(new \DateInterval('P1D'))->format(format: DateTimeInterface::RFC3339),
            ],
            'invalid 1 rev' => [
                'startDate' => $date->add(new \DateInterval('P1D'))->format(format: DateTimeInterface::RFC3339),
                'endDate' => $date->format('Y-m-d'),
            ],
            'invalid 2' => [
                'startDate' => $date->format('Y-m-d H:i:s'),
                'endDate' => $date->add(new \DateInterval('P1D'))->format(format: DateTimeInterface::RFC3339),
            ],
            'invalid 2 rev' => [
                'startDate' => $date->add(new \DateInterval('P1D'))->format(format: DateTimeInterface::RFC3339),
                'endDate' => $date->format('Y-m-d H:i:s'),
            ],
            'invalid 3' => [
                'startDate' => 'bla',
                'endDate' => $date->add(new \DateInterval('P1D'))->format(format: DateTimeInterface::RFC3339),
            ],
            'invalid 3 rev' => [
                'startDate' => $date->add(new \DateInterval('P1D'))->format(format: DateTimeInterface::RFC3339),
                'endDate' => 'bla',
            ],
        ];
    }

    /**
     * @dataProvider incorrectParametersDataProvider
     */
    public function incorrectDatesAreRejected(ApiTester $I, Example $data): void
    {
        $I->sendGet(sprintf(
            '/assignment/status-report/%d/%d/%s/%s',
            1000,
            10,
            $data['startDate'],
            $data['endDate'],
        ));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->canSeeResponseIsJson();
    }
}

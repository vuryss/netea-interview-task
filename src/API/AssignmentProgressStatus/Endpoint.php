<?php

declare(strict_types=1);

namespace App\API\AssignmentProgressStatus;

use App\Learning\Assignment;
use App\Learning\LearningContent;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class Endpoint
{
    /**
     * Unfortunately we cannot use Model with attributes yet :(
     *
     * @OA\Response(
     *     response=200,
     *     description="Assignment status report",
     *     @Model(type=Response::class)
     * )
     */
    #[Route(
        path: '/assignment/status-report/{duration}/{progressPercentage}/{startDate}/{endDate}',
        name: 'assignmentProgressStatus',
        requirements: [
            'duration' => '\d+',
            'progressPercentage' => '\d+'
        ],
        methods: ['GET'],
        format: 'json'
    )]
    #[Parameter(
        name: 'duration',
        in: 'path',
        schema: new Schema(
            description: 'Duration of the learning content in the assignment in seconds',
            type: 'integer',
            maximum: 2 ** 32,
            minimum: 1,
            example: 36000,
        )
    )]
    #[Parameter(
        name: 'progressPercentage',
        in: 'path',
        schema: new Schema(
            description: 'Percentage value of the completed assignment progress',
            type: 'integer',
            maximum: 100,
            minimum: 0,
            example: 10,
        )
    )]
    #[Parameter(
        name: 'startDate',
        in: 'path',
        schema: new Schema(
            description: 'RFC3339 formatted assignment start date',
            type: 'string',
            format: 'date-time',
            example: '2022-05-01T08:30:00+02:00',
        )
    )]
    #[Parameter(
        name: 'endDate',
        in: 'path',
        schema: new Schema(
            description: 'RFC3339 formatted assignment end date',
            type: 'string',
            format: 'date-time',
            example: '2022-05-10T10:42:32+00:00',
        )
    )]
    public function getAssignmentStatusReport(StatusReportRequest $request): StatusReportResponse
    {
        $learningContent = new LearningContent($request->duration);
        $assignment = new Assignment($request->startDate, $request->endDate, $learningContent);

        $progressReport = $assignment->generateReportForCurrentProgress($request->progressPercentage);

        $response = new StatusReportResponse();

        $response->progressStatus = $progressReport['progress_status'];
        $response->expectedProgress = $progressReport['expected_progress'];
        $response->neededDailyLearningTime = $progressReport['needed_daily_learning_time'];

        return $response;
    }
}

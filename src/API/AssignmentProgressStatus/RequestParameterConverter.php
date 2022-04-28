<?php

declare(strict_types=1);

namespace App\API\AssignmentProgressStatus;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestParameterConverter implements ParamConverterInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly DenormalizerInterface $denormalizer,
    ) {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        if (
            !$request->attributes->has('startDate')
            || !$request->attributes->has('endDate')
            || !$request->attributes->has('duration')
            || !$request->attributes->has('progressPercentage')
        ) {
            return false;
        }

        try {
            $requestObject = $this->denormalizer->denormalize(
                [
                    'startDate' => $request->attributes->get('startDate'),
                    'endDate' => $request->attributes->get('endDate'),
                    'duration' => (int)$request->attributes->get('duration'),
                    'progressPercentage' => (int)$request->attributes->get('progressPercentage'),
                ],
                StatusReportRequest::class
            );
        } catch (ExceptionInterface $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $errors = $this->validator->validate($requestObject);

        if (count($errors) > 0) {
            if ($errors instanceof Stringable) {
                throw new BadRequestHttpException((string) $errors);
            }

            throw new BadRequestHttpException('Request validation failed.');
        }

        $request->attributes->set($configuration->getName(), $requestObject);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return is_a($configuration->getClass(), StatusReportRequest::class, true);
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class ErrorNormalizer implements ContextAwareNormalizerInterface, CacheableSupportsMethodInterface
{
    private bool $debug;
    private array $defaultContext = [
        'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
        'title' => 'An error occurred',
    ];

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof FlattenException) {
            throw new InvalidArgumentException(sprintf('The object must implement "%s".', FlattenException::class));
        }

        $context += $this->defaultContext;
        $debug = $this->debug && ($context['debug'] ?? true);

        $data = [
            'type' => $context['type'],
            'title' => $context['title'],
            'status' => $context['status'] ?? $object->getStatusCode(),
            'detail' => $object->getMessage(),
        ];

        if ($debug) {
            $data['class'] = $object->getClass();
            $data['trace'] = $object->getTrace();
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof FlattenException;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

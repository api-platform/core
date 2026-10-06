<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\State\Util;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/** @internal */
final class MercureOptionsResolver
{
    private const ALLOWED_KEYS = [
        'topics' => true,
        'data' => true,
        'private' => true,
        'private_fields' => true,
        'id' => true,
        'type' => true,
        'retry' => true,
        'normalization_context' => true,
        'hub' => true,
        'enable_async_update' => true,
    ];
    private readonly ?ExpressionLanguage $expressionLanguage;

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, ?ExpressionLanguage $expressionLanguage = null)
    {
        $this->expressionLanguage = $expressionLanguage ?? (class_exists(ExpressionLanguage::class) ? new ExpressionLanguage() : null);
        if ($this->expressionLanguage) {
            $rawurlencode = ExpressionFunction::fromPhp('rawurlencode', 'escape');
            $this->expressionLanguage->addFunction($rawurlencode);

            $this->expressionLanguage->addFunction(
                new ExpressionFunction('get_operation', static fn (string $apiResource, string $name): string => \sprintf('getOperation(%s, %s)', $apiResource, $name), static fn (array $arguments, $apiResource, string $name): Operation => $resourceMetadataFactory->create($resourceClassResolver->getResourceClass($apiResource))->getOperation($name))
            );
            $this->expressionLanguage->addFunction(
                new ExpressionFunction('iri', static fn (string $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL, ?string $operation = null): string => \sprintf('iri(%s, %d, %s)', $apiResource, $referenceType, $operation), static fn (array $arguments, $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL, $operation = null): string => $iriConverter->getIriFromResource($apiResource, $referenceType, $operation))
            );
        }
    }

    public function resolve(Operation $operation, ?object $object, ?string $resourceClass = null): ?array
    {
        $resourceClass ??= $operation->getClass();
        $options = $operation->getMercure() ?? false;
        if (\is_string($options)) {
            if (null === $this->expressionLanguage) {
                throw new RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
            }
            $options = $this->expressionLanguage->evaluate($options, ['object' => $object]);
        }
        if (false === $options) {
            return null;
        }
        if (true === $options) {
            $options = [];
        }
        if (!\is_array($options)) {
            throw new InvalidArgumentException(\sprintf('The value of the "mercure" attribute of the "%s" resource class must be a boolean, an array of options or an expression returning this array, "%s" given.', $resourceClass, \gettype($options)));
        }
        foreach ($options as $key => $value) {
            if (!isset(self::ALLOWED_KEYS[$key])) {
                throw new InvalidArgumentException(\sprintf('The option "%s" set in the "mercure" attribute of the "%s" resource does not exist. Existing options: "%s"', $key, $resourceClass, implode('", "', array_keys(self::ALLOWED_KEYS))));
            }
        }
        if ([] !== ($options['private_fields'] ?? []) && !($options['private'] ?? false)) {
            throw new InvalidArgumentException('"private_fields" requires "mercure.private" to be true.');
        }
        $options['enable_async_update'] ??= true;

        return $options;
    }

    public function evaluateTopics(array &$options, object $object): void
    {
        if (!($options['topics'] ?? false)) {
            return;
        }

        $topics = [];
        foreach ((array) $options['topics'] as $topic) {
            if (!\is_string($topic)) {
                $topics[] = $topic;
                continue;
            }

            if (!str_starts_with($topic, '@=')) {
                $topics[] = $topic;
                continue;
            }

            if (null === $this->expressionLanguage) {
                throw new \LogicException('The "@=" expression syntax cannot be used without the Expression Language component. Try running "composer require symfony/expression-language".');
            }

            $topics[] = $this->expressionLanguage->evaluate(substr($topic, 2), ['object' => $object]);
        }

        $options['topics'] = $topics;
    }
}

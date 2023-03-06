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

namespace ApiPlatform\GraphQl\Resolver\Stage;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\ArrayTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ReadStage implements ReadStageInterface
{
    use ArrayTrait;
    use ClassInfoTrait;
    use IdentifierTrait;

    public function __construct(private readonly IriConverterInterface $iriConverter, private readonly ProviderInterface $provider, private readonly SerializerContextBuilderInterface $serializerContextBuilder, private readonly string $nestingSeparator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(?string $resourceClass, ?string $rootClass, Operation $operation, array $context): object|array|null
    {
        if (!($operation->canRead() ?? true)) {
            return $context['is_collection'] ? [] : null;
        }

        $args = $context['args'];
        $normalizationContext = $this->serializerContextBuilder->create($resourceClass, $operation, $context, true);

        if (!$context['is_collection']) {
            $identifier = $this->getIdentifierFromContext($context);
            $item = $this->getItem($identifier, $normalizationContext);

            if ($identifier && ($context['is_mutation'] || $context['is_subscription'])) {
                if (null === $item) {
                    throw new NotFoundHttpException(sprintf('Item "%s" not found.', $args['input']['id']));
                }

                if ($resourceClass !== $this->getObjectClass($item)) {
                    throw new \UnexpectedValueException(sprintf('Item "%s" did not match expected type "%s".', $args['input']['id'], $operation->getShortName()));
                }
            }

            return $item;
        }

        if (null === $rootClass) {
            return [];
        }

        $uriVariables = [];
        $normalizationContext['filters'] = $this->getNormalizedFilters($args);
        $normalizationContext['operation'] = $operation;

        $source = $context['source'];
        /** @var ResolveInfo $info */
        $info = $context['info'];
        if (isset($source[$info->fieldName], $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY], $source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
            $uriVariables = $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY];
            $normalizationContext['linkClass'] = $source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY];
            $normalizationContext['linkProperty'] = $info->fieldName;
        }

        return $this->provider->provide($operation, $uriVariables, $normalizationContext);
    }

    private function getItem(?string $identifier, array $normalizationContext): ?object
    {
        if (null === $identifier) {
            return null;
        }

        try {
            $item = $this->iriConverter->getResourceFromIri($identifier, $normalizationContext);
        } catch (ItemNotFoundException) {
            return null;
        }

        return $item;
    }

    private function getNormalizedFilters(array $args): array
    {
        $filters = $args;

        foreach ($filters as $name => $value) {
            if (\is_array($value)) {
                if (strpos($name, '_list')) {
                    $name = substr($name, 0, \strlen($name) - \strlen('_list'));
                }

                // If the value contains arrays, we need to merge them for the filters to understand this syntax, proper to GraphQL to preserve the order of the arguments.
                if ($this->isSequentialArrayOfArrays($value)) {
                    $value = array_merge(...$value);
                }
                $filters[$name] = $this->getNormalizedFilters($value);
            }

            if (\is_string($name) && strpos($name, $this->nestingSeparator)) {
                // Gives a chance to relations/nested fields.
                $index = array_search($name, array_keys($filters), true);
                $filters =
                    \array_slice($filters, 0, $index + 1) +
                    [str_replace($this->nestingSeparator, '.', $name) => $value] +
                    \array_slice($filters, $index + 1);
            }
        }

        return $filters;
    }
}

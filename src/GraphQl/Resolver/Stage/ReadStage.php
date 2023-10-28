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

use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read stage of GraphQL resolvers.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ReadStage implements ReadStageInterface
{
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

    public function isSequentialArrayOfArrays(array $array): bool
    {
        if (!$this->isSequentialArray($array)) {
            return false;
        }

        return $this->arrayContainsOnly($array, 'array');
    }

    public function isSequentialArray(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_is_list($array);
    }

    public function arrayContainsOnly(array $array, string $type): bool
    {
        return $array === array_filter($array, static fn ($item): bool => $type === \gettype($item));
    }

    /**
     * Get class name of the given object.
     */
    private function getObjectClass(object $object): string
    {
        return $this->getRealClassName($object::class);
    }

    /**
     * Get the real class name of a class name that could be a proxy.
     */
    private function getRealClassName(string $className): string
    {
        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        $positionCg = strrpos($className, '\\__CG__\\');
        $positionPm = strrpos($className, '\\__PM__\\');

        if (false === $positionCg && false === $positionPm) {
            return $className;
        }

        if (false !== $positionCg) {
            return substr($className, $positionCg + 8);
        }

        $className = ltrim($className, '\\');

        return substr(
            $className,
            8 + $positionPm,
            strrpos($className, '\\') - ($positionPm + 8)
        );
    }
}

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

namespace ApiPlatform\GraphQl\State\Provider;

use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\GraphQl\Util\ArrayTrait;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reads data of the provided args, it is also called when reading relations in the same query.
 */
final class ReadProvider implements ProviderInterface
{
    use ArrayTrait;
    use ClassInfoTrait;
    use IdentifierTrait;

    public function __construct(private readonly ProviderInterface $provider, private readonly IriConverterInterface $iriConverter, private readonly ?SerializerContextBuilderInterface $serializerContextBuilder, private readonly string $nestingSeparator)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof GraphQlOperation || !($operation->canRead() ?? true)) {
            return $operation instanceof QueryCollection ? [] : null;
        }

        $args = $context['args'] ?? [];

        if ($this->serializerContextBuilder) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            $context += $this->serializerContextBuilder->create($operation->getClass(), $operation, $context, true);
        }

        if (!$operation instanceof CollectionOperationInterface) {
            $identifier = $this->getIdentifierFromOperation($operation, $args);

            if (!$identifier) {
                return null;
            }

            try {
                $item = $this->iriConverter->getResourceFromIri($identifier, $context);
            } catch (ItemNotFoundException) {
                $item = null;
            }

            if ($operation instanceof Subscription || $operation instanceof Mutation) {
                if (null === $item) {
                    throw new NotFoundHttpException(sprintf('Item "%s" not found.', $args['input']['id']));
                }

                if ($operation->getClass() !== $this->getObjectClass($item)) {
                    throw new \UnexpectedValueException(sprintf('Item "%s" did not match expected type "%s".', $args['input']['id'], $operation->getShortName()));
                }
            }

            if (null === $item) {
                return $item;
            }

            if (!\is_object($item)) {
                throw new \LogicException('Item from read provider should be a nullable object.');
            }

            if (isset($context['graphql_context']) && !enum_exists($item::class)) {
                $context['graphql_context']['previous_object'] = clone $item;
            }

            return $item;
        }

        if (null === ($context['root_class'] ?? null)) {
            return [];
        }

        $uriVariables = [];
        $context['filters'] = $this->getNormalizedFilters($args);

        // This is how we resolve graphql links see ApiPlatform\Doctrine\Common\State\LinksHandlerTrait, I'm wondering if we couldn't do that in an UriVariables
        // resolver within our ApiPlatform\GraphQl\Resolver\Factory\ResolverFactory, this would mimic what's happening in the HTTP controller and simplify some code.
        $source = $context['source'];
        /** @var \GraphQL\Type\Definition\ResolveInfo $info */
        $info = $context['info'];
        if (isset($source[$info->fieldName], $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY], $source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
            $uriVariables = $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY];
            $context['linkClass'] = $source[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY];
            $context['linkProperty'] = $info->fieldName;
        }

        return $this->provider->provide($operation, $uriVariables, $context);
    }

    /**
     * @param array<string, string|array> $args
     *
     * @return array<string, string>
     */
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

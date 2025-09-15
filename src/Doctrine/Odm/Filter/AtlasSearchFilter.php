<?php

declare(strict_types=1);

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class AtlasSearchFilter implements FilterInterface, OpenApiParameterFilterInterface, ManagerRegistryAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use ManagerRegistryAwareTrait;
    use OpenApiFilterTrait;

    /**
     * @see https://www.mongodb.com/docs/atlas/atlas-search/compound/
     *
     * @param string $term The term to use in the Atlas Search query (must, mustNot, should, filter). Default to "must".
     */
    public function __construct(
        private readonly string $index = 'default',
        private readonly string $operator = 'text',
        private readonly string $term = 'must',
        private readonly ?array $facet = null,
    ) {
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];
        $property = $parameter->getProperty();
        $value = $parameter->getValue();

        if (!isset($context['search'])) {
            $searchStage = $context['search']['stage'] = $aggregationBuilder->search();
            $searchStage->index($this->index);

            if ($this->facet) {
                $searchStage->facet()
                    ->operator($compound = $searchStage->compound())
                    ->add(...$this->facet);
            } else {
                $compound = $context['search']['compound'] = $searchStage->compound();
            }
            $context['search']['compound'] = $compound;
        } else {
            $compound = $context['search']['compound'];
        }

        $compound->{$this->term}();

        switch ($this->operator) {
            case 'queryString':
                $operator = $compound->queryString()
                    ->defaultPath($property)
                    ->query($value);
                break;
            case 'wildcard':
                $operator = $compound->wildcard()
                    ->path($property)
                    ->query($value);
                break;
            case 'text':
                $operator = $compound->text()
                    ->path($property)
                    ->query($value)
                    ->fuzzy(maxEdits: 1);
                break;
            case 'phrase':
                $operator = $compound->phrase()
                    ->path($property)
                    ->query($value)
                    ->slop(2);
                break;
            case 'autocomplete':
                $operator = $compound->autocomplete()
                    ->path($property)
                    ->query($value)
                    ->fuzzy(maxEdits: 1);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported operator "%s" for AtlasSearchFilter', $this->operator));
        }
    }
}

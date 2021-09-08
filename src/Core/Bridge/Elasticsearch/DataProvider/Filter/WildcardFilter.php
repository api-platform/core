<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter;

final class WildcardFilter extends AbstractSearchFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getQuery(string $property, array $values, ?string $nestedPath): array
    {
        $matches = [];

        foreach ($values as $value) {
            $matches[] = [strpos($value, '*') ? 'wildcard' : 'match' => [$property => $value]];
        }

        $matchQuery = isset($matches[1]) ? ['bool' => ['should' => $matches]] : $matches[0];

        if (null !== $nestedPath) {
            $matchQuery = ['nested' => ['path' => $nestedPath, 'query' => $matchQuery]];
        }

        return $matchQuery;
    }
}

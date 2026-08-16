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

namespace ApiPlatform\Elasticsearch\Esql;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;

/**
 * Builds an ES|QL query (sent to the Elasticsearch "_query" endpoint).
 *
 * Values and identifiers are bound as named parameters (`?name` for values,
 * `??name` for identifiers such as field names) so the resulting query is
 * safe against injection whatever the source of the bound data is.
 *
 * Full-text conditions (e.g. `MATCH(field, ?value)`) must be part of the first
 * `WHERE` command following `FROM`: they are accumulated separately through
 * {@see fullTextWhere()} and always compiled into that first `WHERE`.
 *
 * @see https://www.elastic.co/docs/reference/query-languages/esql/esql-syntax
 *
 * @experimental
 */
final class EsqlQuery
{
    private ?string $where = null;

    private ?string $fullTextWhere = null;

    /**
     * @var list<string>
     */
    private array $sorts = [];

    private ?int $limit = null;

    /**
     * @var list<array<string, mixed>>
     */
    private array $params = [];

    private int $paramCount = 0;

    /**
     * @param list<string> $metadataFields
     */
    public function __construct(
        private readonly string $index,
        private readonly array $metadataFields = ['_id'],
    ) {
    }

    /**
     * Binds a value and returns its placeholder (e.g. "?p1").
     */
    public function param(mixed $value): string
    {
        $name = 'p'.++$this->paramCount;
        $this->params[] = [$name => $value];

        return "?{$name}";
    }

    /**
     * Binds an identifier (field name) and returns its placeholder (e.g. "??f2").
     */
    public function identifier(string $name): string
    {
        $paramName = 'f'.++$this->paramCount;
        $this->params[] = [$paramName => $name];

        return "??{$paramName}";
    }

    /**
     * Adds a condition joined with a logical AND, like the Doctrine "andWhere" method.
     */
    public function andWhere(string $condition): self
    {
        $this->where = null === $this->where ? $condition : "({$this->where}) AND ({$condition})";

        return $this;
    }

    /**
     * Adds a condition joined with a logical OR, like the Doctrine "orWhere" method.
     */
    public function orWhere(string $condition): self
    {
        $this->where = null === $this->where ? $condition : "({$this->where}) OR ({$condition})";

        return $this;
    }

    /**
     * Adds a full-text condition (e.g. MATCH()), always compiled into the first WHERE command after FROM.
     */
    public function fullTextWhere(string $condition, string $operator = 'AND'): self
    {
        if (!\in_array($operator = strtoupper($operator), ['AND', 'OR'], true)) {
            throw new InvalidArgumentException(\sprintf('Invalid logical operator "%s".', $operator));
        }

        $this->fullTextWhere = null === $this->fullTextWhere ? $condition : "({$this->fullTextWhere}) {$operator} ({$condition})";

        return $this;
    }

    /**
     * @param string $direction "ASC" or "DESC", case-insensitive (validated at runtime as it may come from user input)
     */
    public function sort(string $field, string $direction = 'ASC'): self
    {
        // identifier parameters (??name) are not supported by the SORT command, validate the raw field name instead
        if (!preg_match('/^[a-zA-Z0-9_.@-]+$/', $field)) {
            throw new InvalidArgumentException(\sprintf('Invalid sort field "%s".', $field));
        }

        if (!\in_array($direction = strtoupper($direction), ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException(\sprintf('Invalid sort direction "%s".', $direction));
        }

        $this->sorts[] = "{$field} {$direction}";

        return $this;
    }

    public function hasSort(): bool
    {
        return [] !== $this->sorts;
    }

    public function hasSortOn(string $field): bool
    {
        foreach ($this->sorts as $sort) {
            if (str_starts_with($sort, "{$field} ")) {
                return true;
            }
        }

        return false;
    }

    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('The limit must be greater than or equal to 0.');
        }

        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Compiles to the ES|QL request body: the query string and its named parameters.
     *
     * @return array{query: string, params: list<array<string, mixed>>}
     */
    public function compile(): array
    {
        $index = $this->index;
        if (!preg_match('/^[a-zA-Z0-9_.*-]+$/', $index)) {
            throw new InvalidArgumentException(\sprintf('Invalid index name "%s".', $index));
        }

        $commands = [\sprintf('FROM %s%s', $index, $this->metadataFields ? ' METADATA '.implode(', ', $this->metadataFields) : '')];

        $where = match (true) {
            null !== $this->fullTextWhere && null !== $this->where => "({$this->fullTextWhere}) AND ({$this->where})",
            null !== $this->fullTextWhere => $this->fullTextWhere,
            default => $this->where,
        };

        if (null !== $where) {
            $commands[] = "WHERE {$where}";
        }

        if ($this->sorts) {
            $commands[] = 'SORT '.implode(', ', $this->sorts);
        }

        if (null !== $this->limit) {
            $commands[] = "LIMIT {$this->limit}";
        }

        return [
            'query' => implode(' | ', $commands),
            'params' => $this->params,
        ];
    }
}

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

namespace ApiPlatform\Symfony\Bundle\Command\Upgrade;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Rewrites legacy `#[ApiFilter]` declarations on a resource class to `QueryParameter`
 * entries on the `#[ApiResource]` attribute, dropping the now-unused imports.
 *
 * @internal
 */
final class UpgradeApiFilterVisitor extends NodeVisitorAbstract
{
    /** DateFilter null-management mode value => the constant name to reference on the filter class. */
    private const FILTER_CONTEXT_CONSTANTS = [
        'exclude_null' => 'EXCLUDE_NULL',
        'include_null_before' => 'INCLUDE_NULL_BEFORE',
        'include_null_after' => 'INCLUDE_NULL_AFTER',
        'include_null_before_and_after' => 'INCLUDE_NULL_BEFORE_AND_AFTER',
    ];

    /** @var list<string> short names of filter classes referenced by removed `#[ApiFilter]` attributes */
    private array $removedFilterShortNames = [];

    /**
     * @param string                          $className  FQCN of the resource class to transform
     * @param list<UpgradeApiFilterParameter> $parameters resolved QueryParameter targets to inject
     */
    public function __construct(
        private readonly string $className,
        private readonly array $parameters,
    ) {
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Class_ && $this->isTargetClass($node)) {
            $this->removeApiFilterAttributes($node);
            $this->injectParameters($node);
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->rewriteUses($node);
        }

        return null;
    }

    private function isTargetClass(Node\Stmt\Class_ $node): bool
    {
        return null !== $node->name && $node->name->toString() === $this->shortName($this->className);
    }

    private function removeApiFilterAttributes(Node\Stmt\Class_ $node): void
    {
        $this->stripApiFilter($node);

        foreach ($node->getProperties() as $property) {
            $this->stripApiFilter($property);
        }

        $constructor = $node->getMethod('__construct');
        if (null !== $constructor) {
            foreach ($constructor->params as $param) {
                $this->stripApiFilter($param);
            }
        }
    }

    private function stripApiFilter(Node\Stmt\Class_|Node\Stmt\Property|Node\Param $node): void
    {
        foreach ($node->attrGroups as $gi => $group) {
            foreach ($group->attrs as $ai => $attr) {
                if ('ApiFilter' !== $attr->name->getLast()) {
                    continue;
                }

                $firstArg = $attr->args[0] ?? null;
                if ($firstArg?->value instanceof Node\Expr\ClassConstFetch && $firstArg->value->class instanceof Node\Name) {
                    $this->removedFilterShortNames[] = $firstArg->value->class->getLast();
                }

                unset($group->attrs[$ai]);
            }

            $group->attrs = array_values($group->attrs);
            if (!$group->attrs) {
                unset($node->attrGroups[$gi]);
            }
        }

        $node->attrGroups = array_values($node->attrGroups);
    }

    private function injectParameters(Node\Stmt\Class_ $node): void
    {
        if (!$this->parameters) {
            return;
        }

        $items = [];
        foreach ($this->parameters as $parameter) {
            $items[] = new Node\ArrayItem($this->buildQueryParameter($parameter), new Node\Scalar\String_($parameter->key));
        }

        $parametersArg = new Node\Arg(
            new Node\Expr\Array_($items, ['kind' => Node\Expr\Array_::KIND_SHORT]),
            name: new Node\Identifier('parameters'),
        );

        foreach ($node->attrGroups as $group) {
            foreach ($group->attrs as $attr) {
                if ('ApiResource' === $attr->name->getLast()) {
                    $attr->args[] = $parametersArg;

                    return;
                }
            }
        }
    }

    private function buildQueryParameter(UpgradeApiFilterParameter $parameter): Node\Expr\New_
    {
        $filterArgs = [];
        if ($parameter->caseSensitive) {
            $filterArgs[] = new Node\Arg(new Node\Expr\ConstFetch(new Node\Name('true')), name: new Node\Identifier('caseSensitive'));
        }
        foreach ($parameter->arguments as $name => $value) {
            $filterArgs[] = new Node\Arg($this->buildValue($value), name: new Node\Identifier($name));
        }

        $args = [
            new Node\Arg(
                new Node\Expr\New_(new Node\Name($this->shortName($parameter->filterClass)), $filterArgs),
                name: new Node\Identifier('filter'),
            ),
        ];

        if (null !== $parameter->property) {
            $args[] = new Node\Arg(new Node\Scalar\String_($parameter->property), name: new Node\Identifier('property'));
        }

        if (null !== $parameter->nativeType) {
            $args[] = new Node\Arg($this->buildNativeType($parameter->nativeType), name: new Node\Identifier('nativeType'));
        }

        if ($parameter->castToNativeType) {
            $args[] = new Node\Arg(new Node\Expr\ConstFetch(new Node\Name('true')), name: new Node\Identifier('castToNativeType'));
        }

        if (null !== $parameter->filterContext) {
            $args[] = new Node\Arg($this->buildFilterContext($parameter), name: new Node\Identifier('filterContext'));
        }

        return new Node\Expr\New_(new Node\Name('QueryParameter'), $args);
    }

    /**
     * Re-expresses a DateFilter null-management mode as the `DateFilter::INCLUDE_NULL_*` constant it
     * came from (the filter class is already imported), falling back to a string literal otherwise.
     */
    private function buildFilterContext(UpgradeApiFilterParameter $parameter): Node\Expr
    {
        $constant = self::FILTER_CONTEXT_CONSTANTS[$parameter->filterContext] ?? null;
        if (null === $constant) {
            return new Node\Scalar\String_($parameter->filterContext);
        }

        return new Node\Expr\ClassConstFetch(new Node\Name($this->shortName($parameter->filterClass)), new Node\Identifier($constant));
    }

    private function buildValue(mixed $value): Node\Expr
    {
        return BuilderHelpers::normalizeValue($value);
    }

    private function buildNativeType(string $nativeType): Node\Expr\New_
    {
        $case = match ($nativeType) {
            'bool' => 'BOOL',
            'int' => 'INT',
            'float' => 'FLOAT',
            default => 'STRING',
        };

        return new Node\Expr\New_(new Node\Name('BuiltinType'), [
            new Node\Arg(new Node\Expr\ClassConstFetch(new Node\Name('TypeIdentifier'), new Node\Identifier($case))),
        ]);
    }

    private function rewriteUses(Node\Stmt\Namespace_ $node): void
    {
        // Filters reused as the canonical target (survivors, custom service filters) keep their import.
        $keepShortNames = array_map(fn (UpgradeApiFilterParameter $p): string => $this->shortName($p->filterClass), $this->parameters);
        $removeShortNames = array_diff(array_merge(['ApiFilter'], $this->removedFilterShortNames), $keepShortNames);
        $existing = [];

        foreach ($node->stmts as $k => $stmt) {
            if (!$stmt instanceof Node\Stmt\Use_) {
                continue;
            }

            foreach ($stmt->uses as $use) {
                if (\in_array($use->name->getLast(), $removeShortNames, true)) {
                    unset($node->stmts[$k]);
                    continue 2;
                }

                $existing[$use->name->toString()] = true;
            }
        }

        $node->stmts = array_values($node->stmts);

        $imports = [];
        foreach ($this->parameters as $parameter) {
            $imports[$parameter->filterClass] = true;
            $imports[QueryParameter::class] = true;
            if (null !== $parameter->nativeType) {
                $imports[BuiltinType::class] = true;
                $imports[TypeIdentifier::class] = true;
            }
        }

        $toAdd = [];
        foreach (array_keys($imports) as $fqcn) {
            if (!isset($existing[$fqcn])) {
                $toAdd[] = $fqcn;
            }
        }

        sort($toAdd);
        foreach (array_reverse($toAdd) as $fqcn) {
            array_unshift($node->stmts, new Node\Stmt\Use_([new Node\UseItem(new Node\Name($fqcn))]));
        }
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}

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

namespace ApiPlatform\Core\Upgrade;

use ApiPlatform\Core\Annotation\ApiFilter as LegacyApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter as LegacyBooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter as LegacyDateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter as LegacyExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter as LegacyNumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter as LegacyOrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter as LegacyRangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter as LegacySearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class UpgradeApiFilterVisitor extends NodeVisitorAbstract
{
    use DeprecationMetadataTrait;
    use RemoveAnnotationTrait;

    private ?AnnotationReader $reader;
    private \ReflectionClass $reflectionClass;

    public function __construct(?AnnotationReader $reader, string $resourceClass)
    {
        $this->reader = $reader;
        $this->reflectionClass = new \ReflectionClass($resourceClass);
    }

    /**
     * @return int|Node|null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $namespaces = [
                ApiFilter::class,
                SearchFilter::class,
                ExistsFilter::class,
                DateFilter::class,
                BooleanFilter::class,
                NumericFilter::class,
                OrderFilter::class,
                RangeFilter::class,
            ];

            foreach ($node->stmts as $k => $stmt) {
                if (!$stmt instanceof Node\Stmt\Use_) {
                    break;
                }

                $useStatement = implode('\\', $stmt->uses[0]->name->parts);

                if (LegacyApiFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }
                if (LegacySearchFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }
                if (LegacyExistsFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }
                if (LegacyDateFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }
                if (LegacyBooleanFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }
                if (LegacyNumericFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }
                if (LegacyOrderFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }
                if (LegacyRangeFilter::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }

                if (false !== ($key = array_search($useStatement, $namespaces, true))) {
                    unset($namespaces[$key]);
                }
            }

            foreach ($namespaces as $namespace) {
                array_unshift($node->stmts, new Node\Stmt\Use_([
                    new Node\Stmt\UseUse(
                        new Node\Name(
                            $namespace
                        )
                    ),
                ]));
            }
        }

        if ($node instanceof Node\Stmt\Property || $node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_) {
            if ($node instanceof Node\Stmt\Property) {
                $reflection = $this->reflectionClass->getProperty($node->props[0]->name->__toString());
            } else {
                $reflection = $this->reflectionClass;
            }

            foreach ($this->readApiFilters($reflection) as $annotation) {
                [$filterAnnotation, $isAnnotation] = $annotation;
                if ($isAnnotation) {
                    $this->removeAnnotation($node);
                } else {
                    $this->removeAttribute($node);
                }

                $arguments = [];

                foreach ([
                    'strategy',
                    'filterClass',
                    'properties',
                    'arguments',
                ] as $key) {
                    $value = $filterAnnotation->{$key};

                    if (!$value) {
                        continue;
                    }
                    $arguments[$key] = $this->valueToNode($value);
                }

                array_unshift($node->attrGroups, new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name('ApiFilter'),
                        $this->arrayToArguments($arguments),
                    ),
                ]));
            }
        }
    }

    private function readApiFilters(\ReflectionProperty|\ReflectionClass|\ReflectionInterface $reflection): ?\Generator
    {
        if (\PHP_VERSION_ID >= 80000 && $attributes = $reflection->getAttributes(LegacyApiFilter::class)) {
            yield from array_map(function ($attribute) {
                return [$attribute->newInstance(), false];
            }, $attributes);
        }

        if (null === $this->reader) {
            throw new \RuntimeException(sprintf('Resource "%s" not found.', $reflection->getDeclaringClass()->getName()));
        }

        if ($reflection instanceof \ReflectionProperty) {
            $annotations = $this->reader->getPropertyAnnotations($reflection);
        } else {
            $annotations = $this->reader->getClassAnnotations($reflection);
        }

        foreach ($annotations as $annotation) {
            if ($annotation instanceof LegacyApiFilter) {
                yield [$annotation, true];
            }
        }
    }

    private function valueToNode(mixed $value)
    {
        if (\is_string($value)) {
            if (class_exists($value)) {
                return new Node\Expr\ClassConstFetch(new Node\Name($this->getShortName($value)), 'class');
            }

            return new Node\Scalar\String_($value);
        }

        if (\is_bool($value)) {
            return new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false'));
        }

        if (is_numeric($value)) {
            return \is_int($value) ? new Node\Scalar\LNumber($value) : new Node\Scalar\DNumber($value);
        }

        if (\is_array($value)) {
            return new Node\Expr\Array_(
                array_map(function ($key, $value) {
                    return new Node\Expr\ArrayItem(
                        $this->valueToNode($value),
                        \is_string($key) ? $this->valueToNode($key) : null,
                    );
                }, array_keys($value), array_values($value)),
                [
                    'kind' => Node\Expr\Array_::KIND_SHORT,
                ]
            );
        }
    }

    private function removeAnnotation(Node\Stmt\Property|Node\Stmt\Class_ $node)
    {
        $comment = $node->getDocComment();

        if (preg_match('/@ApiFilter/', $comment->getText())) {
            $node->setDocComment($this->removeAnnotationByTag($comment, 'ApiFilter'));
        }
    }

    private function removeAttribute(Node\Stmt\Property|Node\Stmt\Class_ $node)
    {
        foreach ($node->attrGroups as $k => $attrGroupNode) {
            foreach ($attrGroupNode->attrs as $i => $attribute) {
                if (str_ends_with(implode('\\', $attribute->name->parts), 'ApiFilter')) {
                    unset($node->attrGroups[$k]);
                    break;
                }
            }
        }
    }

    /**
     * @return Node\Arg[]
     */
    private function arrayToArguments(array $arguments)
    {
        $args = [];
        foreach ($arguments as $key => $value) {
            if ($value) {
                $args[] = new Node\Arg($value, false, false, [], new Node\Identifier($key));
            }
        }

        return $args;
    }

    private function getShortName(string $class): string
    {
        if (false !== $pos = strrpos($class, '\\')) {
            return substr($class, $pos + 1);
        }

        return $class;
    }
}

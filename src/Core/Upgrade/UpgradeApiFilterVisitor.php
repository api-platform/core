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
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\BooleanFilter as LegacyOdmBooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter as LegacyOdmDateFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\ExistsFilter as LegacyOdmExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\NumericFilter as LegacyOdmNumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\OrderFilter as LegacyOdmOrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\RangeFilter as LegacyOdmRangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\SearchFilter as LegacyOdmSearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter as LegacyOrmBooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter as LegacyOrmDateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter as LegacyOrmExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter as LegacyOrmNumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter as LegacyOrmOrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter as LegacyOrmRangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter as LegacyOrmSearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter as LegacyGroupFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter as LegacyPropertyFilter;
use ApiPlatform\Doctrine\Odm\Filter\BooleanFilter as OdmBooleanFilter;
use ApiPlatform\Doctrine\Odm\Filter\DateFilter as OdmDateFilter;
use ApiPlatform\Doctrine\Odm\Filter\ExistsFilter as OdmExistsFilter;
use ApiPlatform\Doctrine\Odm\Filter\NumericFilter as OdmNumericFilter;
use ApiPlatform\Doctrine\Odm\Filter\OrderFilter as OdmOrderFilter;
use ApiPlatform\Doctrine\Odm\Filter\RangeFilter as OdmRangeFilter;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter as OdmSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter as OrmBooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter as OrmDateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter as OrmExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter as OrmNumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter as OrmOrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter as OrmRangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter as OrmSearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
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
            $namespaces = [ApiFilter::class];

            $legacyNamespaces = [
                LegacyApiFilter::class => ApiFilter::class,
                LegacyOdmSearchFilter::class => OdmSearchFilter::class,
                LegacyOdmExistsFilter::class => OdmExistsFilter::class,
                LegacyOdmDateFilter::class => OdmDateFilter::class,
                LegacyOdmBooleanFilter::class => OdmBooleanFilter::class,
                LegacyOdmNumericFilter::class => OdmNumericFilter::class,
                LegacyOdmOrderFilter::class => OdmOrderFilter::class,
                LegacyOdmRangeFilter::class => OdmRangeFilter::class,
                LegacyOrmSearchFilter::class => OrmSearchFilter::class,
                LegacyOrmExistsFilter::class => OrmExistsFilter::class,
                LegacyOrmDateFilter::class => OrmDateFilter::class,
                LegacyOrmBooleanFilter::class => OrmBooleanFilter::class,
                LegacyOrmNumericFilter::class => OrmNumericFilter::class,
                LegacyOrmOrderFilter::class => OrmOrderFilter::class,
                LegacyOrmRangeFilter::class => OrmRangeFilter::class,
                LegacyPropertyFilter::class => PropertyFilter::class,
                LegacyGroupFilter::class => GroupFilter::class,
            ];

            foreach ($this->readApiFilters($this->reflectionClass) as $annotation) {
                [$filterAnnotation] = $annotation;
                if (isset($legacyNamespaces[$filterAnnotation->filterClass])) {
                    $namespaces[] = $legacyNamespaces[$filterAnnotation->filterClass];
                }
            }

            foreach ($this->reflectionClass->getProperties() as $reflectionProperty) {
                foreach ($this->readApiFilters($reflectionProperty) as $annotation) {
                    [$filterAnnotation] = $annotation;
                    if (isset($legacyNamespaces[$filterAnnotation->filterClass])) {
                        $namespaces[] = $legacyNamespaces[$filterAnnotation->filterClass];
                    }
                }
            }

            $namespaces = array_unique($namespaces);

            foreach ($node->stmts as $k => $stmt) {
                if (!$stmt instanceof Node\Stmt\Use_) {
                    break;
                }

                $useStatement = implode('\\', $stmt->uses[0]->name->parts);

                foreach (array_keys($legacyNamespaces) as $legacyNamespace) {
                    if ($legacyNamespace === $useStatement) {
                        unset($node->stmts[$k]);
                    }
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
            $reflection = $this->reflectionClass;
            if ($node instanceof Node\Stmt\Property) {
                $reflection = $this->reflectionClass->getProperty($node->props[0]->name->__toString());
            }

            $filterAttributes = [];
            foreach ($this->readApiFilters($reflection) as $annotation) {
                [$filterAnnotation, $isAnnotation] = $annotation;
                if ($isAnnotation) {
                    $this->removeAnnotation($node);
                } else {
                    $this->removeAttribute($node);
                }

                $arguments = [];

                foreach ([
                    'id',
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

                $filterAttributes[] = new Node\Attribute(
                    new Node\Name('ApiFilter'),
                    $this->arrayToArguments($arguments),
                );
            }

            foreach ($filterAttributes as $filterAttribute) {
                $node->attrGroups[] = new Node\AttributeGroup([$filterAttribute]);
            }
        }
    }

    private function readApiFilters(\ReflectionProperty|\ReflectionClass $reflection): ?\Generator
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

    private function removeAnnotation(Node\Stmt\Property|Node\Stmt\Class_|Node\Stmt\Interface_ $node)
    {
        $comment = $node->getDocComment();

        if (preg_match('/@ApiFilter/', $comment->getText())) {
            $node->setDocComment($this->removeAnnotationByTag($comment, 'ApiFilter'));
        }
    }

    private function removeAttribute(Node\Stmt\Property|Node\Stmt\Class_|Node\Stmt\Interface_ $node)
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

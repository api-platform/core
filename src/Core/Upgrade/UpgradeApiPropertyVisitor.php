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

use ApiPlatform\Core\Annotation\ApiProperty as LegacyApiProperty;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class UpgradeApiPropertyVisitor extends NodeVisitorAbstract
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
            $namespaces = [ApiProperty::class];

            foreach ($node->stmts as $k => $stmt) {
                if (!$stmt instanceof Node\Stmt\Use_) {
                    break;
                }

                $useStatement = implode('\\', $stmt->uses[0]->name->parts);

                if (LegacyApiProperty::class === $useStatement) {
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

        if ($node instanceof Node\Stmt\Property || $node instanceof Node\Stmt\ClassMethod) {
            if ($node instanceof Node\Stmt\Property) {
                $reflection = $this->reflectionClass->getProperty($node->props[0]->name->__toString());
            } else {
                $reflection = $this->reflectionClass->getMethod($node->name->__toString());
            }

            [$propertyAnnotation, $isAnnotation] = $this->readApiProperty($reflection);

            if ($propertyAnnotation) {
                if ($isAnnotation) {
                    $this->removeAnnotation($node);
                } else {
                    $this->removeAttribute($node);
                }

                $arguments = [];

                foreach ([
                    'description',
                    'readable',
                    'writable',
                    'readableLink',
                    'writableLink',
                    'required',
                    'iri',
                    'identifier',
                    'default',
                    'example',
                    'types',
                    'builtinTypes',
                ] as $key) {
                    if (null === ($value = $propertyAnnotation->{$key}) || (\in_array($key, ['types', 'builtinTypes'], true) && [] === $value)) {
                        continue;
                    }

                    if ('iri' === $key) {
                        $arguments['iris'] = new Node\Expr\Array_([new Node\Expr\ArrayItem(
                            new Node\Scalar\String_($value)
                        )], ['kind' => Node\Expr\Array_::KIND_SHORT]);
                        continue;
                    }

                    $arguments[$key] = $this->valueToNode($value);
                }

                foreach ($propertyAnnotation->attributes ?? [] as $key => $value) {
                    if (null === $value) {
                        continue;
                    }

                    [$key, $value] = $this->getKeyValue($key, $value);
                    $arguments[$key] = $this->valueToNode($value);
                }

                array_unshift($node->attrGroups, new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name('ApiProperty'),
                        $this->arrayToArguments($arguments)
                    ),
                ]));
            }
        }
    }

    /**
     * @return array<ApiProperty, bool>|null
     */
    private function readApiProperty(\ReflectionProperty|\ReflectionMethod $reflection): ?array
    {
        if (\PHP_VERSION_ID >= 80000 && $attributes = $reflection->getAttributes(LegacyApiProperty::class)) {
            return [$attributes[0]->newInstance(), false];
        }

        if (null === $this->reader) {
            throw new \RuntimeException(sprintf('Resource "%s" not found.', $reflection->getDeclaringClass()->getName()));
        }

        if ($reflection instanceof \ReflectionMethod) {
            $annotation = $this->reader->getMethodAnnotation($reflection, LegacyApiProperty::class);
        } else {
            $annotation = $this->reader->getPropertyAnnotation($reflection, LegacyApiProperty::class);
        }

        if ($annotation) {
            return [$annotation, true];
        }

        return null;
    }

    private function removeAttribute(Node\Stmt\Property|Node\Stmt\ClassMethod $node)
    {
        foreach ($node->attrGroups as $k => $attrGroupNode) {
            foreach ($attrGroupNode->attrs as $i => $attribute) {
                if (str_ends_with(implode('\\', $attribute->name->parts), 'ApiProperty')) {
                    unset($node->attrGroups[$k]);
                    break;
                }
            }
        }
    }

    private function removeAnnotation(Node\Stmt\Property|Node\Stmt\ClassMethod $node)
    {
        $comment = $node->getDocComment();

        if (preg_match('/@ApiProperty/', $comment->getText())) {
            $node->setDocComment($this->removeAnnotationByTag($comment, 'ApiProperty'));
        }
    }

    /**
     * @return Node\Arg[]
     */
    private function arrayToArguments(array $arguments)
    {
        $args = [];
        foreach ($arguments as $key => $value) {
            $args[] = new Node\Arg($value, false, false, [], new Node\Identifier($key));
        }

        return $args;
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

    private function getShortName(string $class): string
    {
        if (false !== $pos = strrpos($class, '\\')) {
            return substr($class, $pos + 1);
        }

        return $class;
    }
}

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

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Annotation\ApiResource as LegacyApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class UpgradeApiResourceVisitor extends NodeVisitorAbstract
{
    use DeprecationMetadataTrait;
    use RemoveAnnotationTrait;

    private LegacyApiResource $resourceAnnotation;
    private IdentifiersExtractorInterface $identifiersExtractor;
    private bool $isAnnotation;
    private string $resourceClass;

    public function __construct(LegacyApiResource $resourceAnnotation, bool $isAnnotation, IdentifiersExtractorInterface $identifiersExtractor, string $resourceClass)
    {
        $this->resourceAnnotation = $resourceAnnotation;
        $this->isAnnotation = $isAnnotation;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->resourceClass = $resourceClass;
    }

    /**
     * In API Platform 3.x there's no difference between items and collections other then a flag within the Operation
     * Therefore we need to fix the behavior with an empty array.
     */
    private function getLegacyOperations(bool $isCollection = false): array
    {
        $key = $isCollection ? 'collectionOperations' : 'itemOperations';
        if ([] === $this->resourceAnnotation->{$key}) {
            return [];
        }

        $default = $isCollection ? ['post', 'get'] : ['get', 'put', 'patch', 'delete'];

        return $this->resourceAnnotation->{$key} ?? $default;
    }

    /**
     * @return int|Node|null
     */
    public function enterNode(Node $node)
    {
        // We don't go through every resources to remove ApiSubresource annotations, do this here as well if there are some
        // @see UpgradeApiSubresourceVisitor
        $comment = $node->getDocComment();
        if ($comment && preg_match('/@ApiSubresource/', $comment->getText())) {
            $node->setDocComment($this->removeAnnotationByTag($comment, 'ApiSubresource'));
        }

        if ($node instanceof Node\Stmt\Namespace_) {
            $namespaces = array_unique(array_merge(
                [ApiResource::class],
                $this->getOperationsNamespaces($this->getLegacyOperations()),
                $this->getOperationsNamespaces($this->getLegacyOperations(true), true),
                $this->getGraphQlOperationsNamespaces($this->resourceAnnotation->graphql ?? [])
            ));

            if (true === !($this->resourceAnnotation->attributes['composite_identifier'] ?? true)) {
                $namespaces[] = Link::class;
            }

            foreach ($node->stmts as $k => $stmt) {
                if (!$stmt instanceof Node\Stmt\Use_) {
                    break;
                }

                $useStatement = implode('\\', $stmt->uses[0]->name->parts);

                if (LegacyApiResource::class === $useStatement) {
                    unset($node->stmts[$k]);
                    continue;
                }

                // There might be a use left as the UpgradeApiSubresourceVisitor doesn't go through all the resources
                if (ApiSubresource::class === $useStatement) {
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

        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_) {
            $this->removeAnnotation($node);
            $this->removeAttribute($node);

            $arguments = [];
            $operations = null === $this->resourceAnnotation->itemOperations && null === $this->resourceAnnotation->collectionOperations ? null : array_merge(
                $this->legacyOperationsToOperations($this->getLegacyOperations()),
                $this->legacyOperationsToOperations($this->getLegacyOperations(true), true)
            );

            if (null !== $operations) {
                $arguments['operations'] = new Node\Expr\Array_(
                    array_map(function ($value) {
                        return new Node\Expr\ArrayItem($value);
                    }, $operations),
                    [
                        'kind' => Node\Expr\Array_::KIND_SHORT,
                    ]
                );
            }

            $graphQlOperations = null === $this->resourceAnnotation->graphql ? null : [];
            foreach ($this->resourceAnnotation->graphql ?? [] as $operationName => $graphQlOperation) {
                if (\is_int($operationName)) {
                    $ns = $this->getGraphQlOperationNamespace($graphQlOperation);
                    $graphQlOperations[] = new Node\Expr\New_(new Node\Name($this->getShortName($ns)), $this->arrayToArguments(['name' => $this->valueToNode($graphQlOperation)]));
                    continue;
                }

                $ns = $this->getGraphQlOperationNamespace($operationName, $graphQlOperation);
                $args = ['name' => $this->valueToNode($operationName)];
                foreach ($graphQlOperation as $key => $value) {
                    [$key, $value] = $this->getKeyValue($key, $value);
                    $args[$key] = $this->valueToNode($value);
                }

                $graphQlOperations[] = new Node\Expr\New_(new Node\Name($this->getShortName($ns)), $this->arrayToArguments($args));
            }

            if (null !== $graphQlOperations) {
                $arguments['graphQlOperations'] = new Node\Expr\Array_(
                    array_map(function ($value) {
                        return new Node\Expr\ArrayItem($value);
                    }, $graphQlOperations),
                    [
                        'kind' => Node\Expr\Array_::KIND_SHORT,
                    ]
                );
            }

            foreach (['shortName', 'description', 'iri'] as $key) {
                if (!($value = $this->resourceAnnotation->{$key})) {
                    continue;
                }

                if ('iri' === $key) {
                    $arguments['types'] = new Node\Expr\Array_([new Node\Expr\ArrayItem(
                        new Node\Scalar\String_($value)
                    )], ['kind' => Node\Expr\Array_::KIND_SHORT]);
                    continue;
                }

                $arguments[$key] = new Node\Scalar\String_($value);
            }

            foreach ($this->resourceAnnotation->attributes ?? [] as $key => $value) {
                if (null === $value) {
                    continue;
                }

                [$key, $value] = $this->getKeyValue($key, $value);

                if ('urlGenerationStrategy' === $key) {
                    $urlGeneratorInterface = new \ReflectionClass(UrlGeneratorInterface::class);
                    $urlGeneratorConstants = array_flip($urlGeneratorInterface->getConstants());
                    $currentUrlGeneratorConstant = $urlGeneratorConstants[$value];

                    $arguments[$key] =
                        new Node\Expr\ClassConstFetch(
                            new Node\Name('UrlGeneratorInterface'),
                            $currentUrlGeneratorConstant
                        );
                    continue;
                }

                if ('compositeIdentifier' === $key) {
                    if (false !== $value) {
                        continue;
                    }

                    $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($this->resourceClass);
                    $identifierNodeItems = [];
                    foreach ($identifiers as $identifier) {
                        $identifierNodes = [
                            'compositeIdentifier' => new Node\Expr\ConstFetch(new Node\Name('false')),
                            'fromClass' => new Node\Expr\ClassConstFetch(
                                new Node\Name(
                                    'self'
                                ),
                                'class'
                            ),
                            'identifiers' => new Node\Expr\Array_(
                                [
                                    new Node\Expr\ArrayItem(new Node\Scalar\String_($identifier)),
                                ],
                                ['kind' => Node\Expr\Array_::KIND_SHORT]
                            ),
                        ];

                        $identifierNodeItems[] = new Node\Expr\ArrayItem(
                            new Node\Expr\New_(new Node\Name('Link'), $this->arrayToArguments($identifierNodes)),
                            new Node\Scalar\String_($identifier)
                        );
                    }

                    $arguments['uriVariables'] = new Node\Expr\Array_($identifierNodeItems, ['kind' => Node\Expr\Array_::KIND_SHORT]);
                    continue;
                }

                $arguments[$key] = $this->valueToNode($value);
            }

            $apiResourceAttribute =
                new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name('ApiResource'),
                        $this->arrayToArguments($arguments)
                    ),
                ]);

            array_unshift($node->attrGroups, $apiResourceAttribute);
        }
    }

    private function getGraphQlOperationNamespace(string $operationName, array $operation = []): string
    {
        switch ($operationName) {
            case 'item_query':
                return Query::class;
            case 'collection_query':
                return QueryCollection::class;
            case 'update':
                return Mutation::class;
            case 'delete':
                return Mutation::class;
            case 'create':
                return Mutation::class;
            default:
                if (isset($operation['item_query'])) {
                    return Query::class;
                }

                if (isset($operation['collection_query'])) {
                    return QueryCollection::class;
                }

                if (isset($operation['mutation'])) {
                    return Mutation::class;
                }

                throw new \LogicException(sprintf('The graphql operation %s is not following API Platform naming convention.', $operationName));
        }
    }

    private function getOperationNamespace(string $method, bool $isCollection = false): string
    {
        switch ($method) {
            case 'POST':
                return Post::class;
            case 'PUT':
                return Put::class;
            case 'PATCH':
                return Patch::class;
            case 'DELETE':
                return Delete::class;
            default:
                return $isCollection ? GetCollection::class : Get::class;
        }
    }

    private function getGraphQlOperationsNamespaces(array $operations): array
    {
        $namespaces = [];
        foreach ($operations as $operationName => $operation) {
            if (\is_string($operationName)) {
                $namespaces[] = $this->getGraphQlOperationNamespace($operationName, $operation);
                continue;
            }

            $namespaces[] = $this->getGraphQlOperationNamespace($operation);
        }

        return $namespaces;
    }

    private function getOperationsNamespaces(array $operations, bool $isCollection = false): array
    {
        $namespaces = [];
        foreach ($operations as $operationName => $operation) {
            if (\is_string($operationName)) {
                $namespaces[] = $this->getOperationNamespace($operation['method'] ?? strtoupper($operationName), $isCollection);
                continue;
            }

            $method = \is_string($operation) ? strtoupper($operation) : $operation['method'];
            $namespaces[] = $this->getOperationNamespace($method, $isCollection);
        }

        return $namespaces;
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

    private function removeAttribute(Node\Stmt\Class_|Node\Stmt\Interface_ $node)
    {
        foreach ($node->attrGroups as $k => $attrGroupNode) {
            foreach ($attrGroupNode->attrs as $i => $attribute) {
                if (str_ends_with(implode('\\', $attribute->name->parts), 'ApiResource')) {
                    unset($node->attrGroups[$k]);
                    break;
                }
            }
        }
        foreach ($node->stmts as $k => $stmts) {
            foreach ($stmts->attrGroups ?? [] as $i => $attrGroups) {
                foreach ($attrGroups->attrs ?? [] as $j => $attrs) {
                    if (str_ends_with(implode('\\', $attrs->name->parts), 'ApiSubresource')) {
                        unset($node->stmts[$k]->attrGroups[$i]);
                        break;
                    }
                }
            }
        }
    }

    private function removeAnnotation(Node\Stmt\Class_|Node\Stmt\Interface_ $node)
    {
        $comment = $node->getDocComment();

        if ($comment && preg_match('/@ApiResource/', $comment->getText())) {
            $node->setDocComment($this->removeAnnotationByTag($comment, 'ApiResource'));
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

    private function getShortName(string $class): string
    {
        if (false !== $pos = strrpos($class, '\\')) {
            return substr($class, $pos + 1);
        }

        return $class;
    }

    private function createOperation(string $namespace, array $arguments = [])
    {
        $args = [];
        foreach ($arguments as $key => $value) {
            [$key, $value] = $this->getKeyValue($key, $value);
            $args[$key] = $this->valueToNode($value);
        }

        return new Node\Expr\New_(new Node\Name($this->getShortName($namespace)), $this->arrayToArguments($args));
    }

    private function legacyOperationsToOperations($legacyOperations, bool $isCollection = false)
    {
        $operations = [];
        foreach ($legacyOperations as $operationName => $operation) {
            if (\is_int($operationName)) {
                $operations[] = $this->createOperation($this->getOperationNamespace(strtoupper($operation), $isCollection));
                continue;
            }

            $method = $operation['method'] ?? strtoupper($operationName);
            unset($operation['method']);
            if (!isset($operation['path']) && !\in_array($operationName, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                $operation['name'] = $operationName;
            }
            $operations[] = $this->createOperation($this->getOperationNamespace($method, $isCollection), $operation);
        }

        return $operations;
    }
}

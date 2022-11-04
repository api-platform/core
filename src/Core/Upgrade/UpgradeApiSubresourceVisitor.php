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
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class UpgradeApiSubresourceVisitor extends NodeVisitorAbstract
{
    use RemoveAnnotationTrait;
    private $subresourceMetadata;
    private $referenceType;

    public function __construct($subresourceMetadata, $referenceType)
    {
        $this->subresourceMetadata = $subresourceMetadata;
        $this->referenceType = $referenceType;
    }

    /**
     * @return int|Node|null
     */
    public function enterNode(Node $node)
    {
        $operationToCreate = $this->subresourceMetadata['collection'] ? GetCollection::class : Get::class;
        $operationUseStatementNeeded = true;
        $apiResourceUseStatementNeeded = true;
        $linkUseStatementNeeded = true;

        $comment = $node->getDocComment();
        if ($comment && preg_match('/@ApiSubresource/', $comment->getText())) {
            $node->setDocComment($this->removeAnnotationByTag($comment, 'ApiSubresource'));
        }

        if ($node instanceof Node\Stmt\Namespace_) {
            foreach ($node->stmts as $i => $stmt) {
                if (!$stmt instanceof Node\Stmt\Use_) {
                    break;
                }

                $useStatement = implode('\\', $stmt->uses[0]->name->parts);
                if (ApiSubresource::class === $useStatement) {
                    unset($node->stmts[$i]);
                }

                if (ApiResource::class === $useStatement) {
                    $apiResourceUseStatementNeeded = false;
                    continue;
                }

                if (Link::class === $useStatement) {
                    $linkUseStatementNeeded = false;
                    continue;
                }

                if ($useStatement === $operationToCreate) {
                    $operationUseStatementNeeded = false;
                    continue;
                }
            }

            if ($operationUseStatementNeeded) {
                array_unshift(
                    $node->stmts,
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                $this->subresourceMetadata['collection'] ? GetCollection::class : Get::class
                            )
                        ),
                    ])
                );
            }

            if ($apiResourceUseStatementNeeded) {
                array_unshift(
                    $node->stmts,
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                ApiResource::class
                            )
                        ),
                    ])
                );
            }

            if ($linkUseStatementNeeded) {
                array_unshift(
                    $node->stmts,
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                Link::class
                            )
                        ),
                    ])
                );
            }
        }

        if ($node instanceof Node\Stmt\Class_) {
            $identifiersNodeItems = [];

            foreach ($this->subresourceMetadata['uri_variables'] as $identifier => $resource) {
                $identifierNodes = [
                    'fromClass' => new Node\Expr\ClassConstFetch(
                        new Node\Name(
                            ($resource['from_class'] === $this->subresourceMetadata['resource_class']) ? 'self' : '\\'.$resource['from_class']
                        ),
                        'class'
                    ),
                    'identifiers' => new Node\Expr\Array_(
                        isset($resource['identifiers'][0]) ? [
                            new Node\Expr\ArrayItem(new Node\Scalar\String_($resource['identifiers'][0])),
                        ] : [],
                        ['kind' => Node\Expr\Array_::KIND_SHORT]
                    ),
                ];

                if (isset($resource['expanded_value'])) {
                    $identifierNodes['expandedValue'] = new Node\Scalar\String_($resource['expanded_value']);
                }

                if (isset($resource['from_property']) || isset($resource['to_property'])) {
                    $identifierNodes[isset($resource['to_property']) ? 'toProperty' : 'fromProperty'] = new Node\Scalar\String_($resource['to_property'] ?? $resource['from_property']);
                }

                $identifierNodeItems[] = new Node\Expr\ArrayItem(
                    new Node\Expr\New_(new Node\Name('Link'), $this->arrayToArguments($identifierNodes)),
                    new Node\Scalar\String_($identifier)
                );
            }

            $arguments = [
                new Node\Arg(
                    new Node\Scalar\String_($this->subresourceMetadata['path']),
                    false,
                    false,
                    [],
                    new Node\Identifier('uriTemplate')
                ),
                new Node\Arg(
                    new Node\Expr\Array_($identifierNodeItems, ['kind' => Node\Expr\Array_::KIND_SHORT]),
                    false,
                    false,
                    [],
                    new Node\Identifier('uriVariables')
                ),
                new Node\Arg(
                    new Node\Scalar\LNumber(200),
                    false,
                    false,
                    [],
                    new Node\Identifier('status')
                ),
            ];

            if (null !== $this->referenceType) {
                $urlGeneratorInterface = new \ReflectionClass(UrlGeneratorInterface::class);
                $urlGeneratorConstants = array_flip($urlGeneratorInterface->getConstants());
                $currentUrlGeneratorConstant = $urlGeneratorConstants[$this->referenceType];

                $arguments[] = new Node\Arg(
                    new Node\Expr\ClassConstFetch(
                        new Node\Name('UrlGeneratorInterface'),
                        $currentUrlGeneratorConstant
                    ),
                    false,
                    false,
                    [],
                    new Node\Identifier('urlGenerationStrategy')
                );
            }

            if ($this->subresourceMetadata['legacy_type'] ?? false) {
                $arguments[] = new Node\Arg(
                    new Node\Expr\ArrayItem(
                        new Node\Expr\Array_(
                            [
                                new Node\Expr\ArrayItem(
                                    new Node\Scalar\String_($this->subresourceMetadata['legacy_type'])
                                ),
                            ],
                            [
                                'kind' => Node\Expr\Array_::KIND_SHORT,
                            ]
                        )
                    ),
                    false,
                    false,
                    [],
                    new Node\Identifier('types')
                );
            }

            if ($this->subresourceMetadata['legacy_filters'] ?? false) {
                $arguments[] = new Node\Arg(
                    new Node\Expr\ArrayItem(
                        new Node\Expr\Array_(
                            array_map(function ($filter) {
                                return new Node\Expr\ArrayItem(
                                    new Node\Scalar\String_($filter)
                                );
                            }, $this->subresourceMetadata['legacy_filters']),
                            [
                                'kind' => Node\Expr\Array_::KIND_SHORT,
                            ]
                        )
                    ),
                    false,
                    false,
                    [],
                    new Node\Identifier('filters')
                );
            }

            if ($this->subresourceMetadata['legacy_normalization_context']['groups'] ?? false) {
                $arguments[] = new Node\Arg(
                    new Node\Expr\ArrayItem(
                        new Node\Expr\Array_([
                            new Node\Expr\ArrayItem(
                                new Node\Expr\Array_(
                                    array_map(function ($group) {
                                        return new Node\Expr\ArrayItem(new Node\Scalar\String_($group));
                                    }, $this->subresourceMetadata['legacy_normalization_context']['groups']),
                                    ['kind' => Node\Expr\Array_::KIND_SHORT]
                                ),
                                new Node\Scalar\String_('groups')
                            ),
                        ], ['kind' => Node\Expr\Array_::KIND_SHORT])
                    ),
                    false,
                    false,
                    [],
                    new Node\Identifier('normalizationContext')
                );
            }

            $arguments[] = new Node\Arg(
                new Node\Expr\Array_(
                    [
                        new Node\Expr\ArrayItem(
                            new Node\Expr\New_(
                                new Node\Name($this->subresourceMetadata['collection'] ? 'GetCollection' : 'Get')
                            ),
                        ),
                    ],
                    [
                        'kind' => Node\Expr\Array_::KIND_SHORT,
                    ]
                ),
                false,
                false,
                [],
                new Node\Identifier('operations')
            );

            $apiResourceAttribute =
                new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name('ApiResource'),
                        $arguments
                    ),
                ]);

            $node->attrGroups[] = $apiResourceAttribute;
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
}

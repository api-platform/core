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

namespace ApiPlatform\Core\Bridge\Rector\Parser;

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

final class TransformApiSubresourceVisitor extends NodeVisitorAbstract
{
    private $subresourceMetadata;
    private $referenceType;

    public function __construct($subresourceMetadata, $referenceType)
    {
        $this->subresourceMetadata = $subresourceMetadata;
        $this->referenceType = $referenceType;
    }

    public function enterNode(Node $node)
    {
        $operationToCreate = $this->subresourceMetadata['collection'] ? GetCollection::class : Get::class;
        $operationUseStatementNeeded = true;

        if ($node instanceof Node\Stmt\Namespace_) {
            foreach ($node->stmts as $stmt) {
                if (!$stmt instanceof Node\Stmt\Use_) {
                    break;
                }

                $useStatement = implode('\\', $stmt->uses[0]->name->parts);
                if ($useStatement === $operationToCreate) {
                    $operationUseStatementNeeded = false;
                    break;
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
        }

        if ($node instanceof Node\Stmt\Class_) {
            $identifiersNodeItems = [];
            foreach ($this->subresourceMetadata['identifiers'] as $identifier => $resource) {
                $identifiersNodeItems[] = new Node\Expr\ArrayItem(
                    new Node\Expr\Array_(
                        [
                            new Node\Expr\ArrayItem(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        ($resource[0] === $this->subresourceMetadata['resource_class']) ? 'self' : '\\'.$resource[0]
                                    ),
                                    'class'
                                )
                            ),
                            new Node\Expr\ArrayItem(
                                new Node\Scalar\String_($resource[1])
                            ),
                        ],
                        [
                            'kind' => Node\Expr\Array_::KIND_SHORT,
                        ]
                    ),
                    new Node\Scalar\String_($identifier)
                );
            }

            $identifiersNode = new Node\Expr\Array_($identifiersNodeItems, ['kind' => Node\Expr\Array_::KIND_SHORT]);

            $arguments = [
                new Node\Arg(
                    new Node\Scalar\String_(str_replace('.{_format}', '', $this->subresourceMetadata['path'])),
                    false,
                    false,
                    [],
                    new Node\Identifier('uriTemplate')
                ),
                new Node\Arg(
                    $identifiersNode,
                    false,
                    false,
                    [],
                    new Node\Identifier('identifiers')
                ),
                new Node\Arg(
                    new Node\Scalar\LNumber(200),
                    false,
                    false,
                    [],
                    new Node\Identifier('status')
                ),
                new Node\Arg(
                    new Node\Expr\ArrayItem(
                        new Node\Expr\Array_(
                            [
                                new Node\Expr\ArrayItem(
                                    new Node\Expr\ConstFetch(new Node\Name('true')),
                                    new Node\Scalar\String_('legacy_subresource_behavior')
                                ),
                                new Node\Expr\ArrayItem(
                                    new Node\Scalar\String_($this->subresourceMetadata['property']),
                                    new Node\Scalar\String_('legacy_subresource_property')
                                ),
                                new Node\Expr\ArrayItem(
                                    new Node\Expr\Array_(
                                        array_map(function ($key, $value) {
                                            return new Node\Expr\ArrayItem(
                                                new Node\Expr\Array_(
                                                    [
                                                        new Node\Expr\ClassConstFetch(
                                                            new Node\Name(
                                                                ($value[0] === $this->subresourceMetadata['resource_class']) ? 'self' : '\\'.$value[0]
                                                            ),
                                                            'class'
                                                        ),
                                                        new Node\Scalar\String_($value[1]),
                                                        new Node\Expr\ConstFetch(new Node\Name($value[2] ? 'true' : 'false')),
                                                    ],
                                                    ['kind' => Node\Expr\Array_::KIND_SHORT]
                                                ),
                                                new Node\Scalar\String_($key)
                                            );
                                        }, array_keys($this->subresourceMetadata['legacy_identifiers']), array_values($this->subresourceMetadata['legacy_identifiers'])),
                                        ['kind' => Node\Expr\Array_::KIND_SHORT]
                                    ),
                                    new Node\Scalar\String_('legacy_subresource_identifiers')
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
                    new Node\Identifier('extraProperties')
                ),
            ];

            if (null !== $this->referenceType) {
                $urlGeneratorInterface = new ReflectionClass(UrlGeneratorInterface::class);
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

            $apiResourceAttribute =
                new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name('\\ApiPlatform\\Metadata\\ApiResource'),
                        $arguments
                    ),
                ]);

            $operationAttribute =
                new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name($this->subresourceMetadata['collection'] ? 'GetCollection' : 'Get')
                    ),
                ]);

            $node->attrGroups[] = $apiResourceAttribute;
            $node->attrGroups[] = $operationAttribute;
        }
    }
}

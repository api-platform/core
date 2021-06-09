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

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpAttribute\Printer\PhpAttributeGroupFactory;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @experimental
 */
final class LegacyApiResourceAttributeToApiResourceAttributeRector extends AbstractLegacyApiResourceToApiResourceAttribute implements ConfigurableRectorInterface
{
    /**
     * @var string
     */
    public const REMOVE_INITIAL_ATTRIBUTE = 'remove_initial_attribute';

    private bool $removeInitialAttribute;

    public function __construct(PhpAttributeGroupFactory $phpAttributeGroupFactory)
    {
        $this->phpAttributeGroupFactory = $phpAttributeGroupFactory;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Upgrade Legacy ApiResource attribute to ApiResource and Operations attributes', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(collectionOperations: [], itemOperations: ['get', 'get_by_isbn' => ['method' => 'GET', 'path' => '/books/by_isbn/{isbn}.{_format}', 'requirements' => ['isbn' => '.+'], 'identifiers' => 'isbn']])]
class Book
CODE_SAMPLE
            , <<<'CODE_SAMPLE'
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource]
#[Get]
#[Get(name: 'get_by_isbn', uriTemplate: '/books/by_isbn/{isbn}.{_format}', requirements: ['isbn' => '.+'], identifiers: 'isbn')]
class Book
CODE_SAMPLE
            , [self::REMOVE_INITIAL_ATTRIBUTE => true])]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param array<string> $configuration
     */
    public function configure(array $configuration): void
    {
        $this->removeInitialAttribute = $configuration[self::REMOVE_INITIAL_ATTRIBUTE] ?? true;
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($node->attrGroups as $key => $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (!$this->isName($attribute->name, \ApiPlatform\Core\Annotation\ApiResource::class)) {
                    continue;
                }
                $items = $this->createItemsFromArgs($attribute->args);
                $arguments = $this->resolveOperations($items, $node);
                $apiResourceAttributeGroup = $this->phpAttributeGroupFactory->createFromClassWithItems(\ApiPlatform\Metadata\ApiResource::class, $arguments);
                array_unshift($node->attrGroups, $apiResourceAttributeGroup);
            }
        }

        $this->cleanupAttrGroups($node);

        return $node;
    }

    private function createItemsFromArgs(array $args): array
    {
        $items = [];

        foreach ($args as $arg) {
            $itemValue = $this->normalizeNodeValue($arg->value);
            $itemName = $this->normalizeNodeValue($arg->name);
            $items[$itemName] = $itemValue;
        }

        return $items;
    }

    /**
     * @return bool|float|int|string|array<mixed>|Node\Expr
     */
    private function normalizeNodeValue($value)
    {
        if ($value instanceof ClassConstFetch) {
            return sprintf('%s::%s', (string) end($value->class->parts), (string) $value->name);
        }
        if ($value instanceof Array_) {
            return $this->normalizeNodeValue($value->items);
        }
        if ($value instanceof String_) {
            return (string) $value->value;
        }
        if ($value instanceof Identifier) {
            return $value->name;
        }
        if ($value instanceof LNumber) {
            return (int) $value->value;
        }
        if (\is_array($value)) {
            $items = [];
            foreach ($value as $itemKey => $itemValue) {
                if (null === $itemValue->key) {
                    $items[] = $this->normalizeNodeValue($itemValue->value);
                } else {
                    $items[$this->normalizeNodeValue($itemValue->key)] = $this->normalizeNodeValue($itemValue->value);
                }
            }

            return $items;
        }

        return $value;
    }

    /**
     * Remove initial ApiResource attribute from node.
     *
     * @param Class_ $node
     */
    private function cleanupAttrGroups(Node $node): void
    {
        if (false === $this->removeInitialAttribute) {
            return;
        }

        foreach ($node->attrGroups as $key => $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($this->isName($attribute->name, \ApiPlatform\Core\Annotation\ApiResource::class)) {
                    unset($node->attrGroups[$key]);
                    continue 2;
                }
            }
        }
    }
}

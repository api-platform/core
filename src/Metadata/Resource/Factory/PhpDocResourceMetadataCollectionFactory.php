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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * Extracts descriptions from PHPDoc.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PhpDocResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private readonly ?PhpDocParser $phpDocParser;
    private readonly ?Lexer $lexer;

    /** @var array<string, PhpDocNode> */
    private array $docBlocks = [];

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
        $phpDocParser = null;
        $lexer = null;
        if (class_exists(PhpDocParser::class) && class_exists(ParserConfig::class)) {
            $config = new ParserConfig([]);
            $phpDocParser = new PhpDocParser($config, new TypeParser($config, new ConstExprParser($config)), new ConstExprParser($config));
            $lexer = new Lexer($config);
        } elseif (class_exists(PhpDocParser::class)) {
            $phpDocParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser()); // @phpstan-ignore-line
            $lexer = new Lexer(); // @phpstan-ignore-line
        }
        $this->phpDocParser = $phpDocParser;
        $this->lexer = $lexer;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            if (null !== $resourceMetadata->getDescription()) {
                continue;
            }

            $description = $this->getShortDescription($resourceClass);

            if (!$description) {
                return $resourceMetadataCollection;
            }

            $resourceMetadataCollection[$key] = $resourceMetadata->withDescription($description);

            $operations = $resourceMetadata->getOperations() ?? new Operations();
            foreach ($operations as $operationName => $operation) {
                if (null !== $operation->getDescription()) {
                    continue;
                }

                $operations->add($operationName, $operation->withDescription($description));
            }

            $resourceMetadataCollection[$key] = $resourceMetadataCollection[$key]->withOperations($operations);

            if (!$resourceMetadata->getGraphQlOperations()) {
                continue;
            }

            foreach ($graphQlOperations = $resourceMetadata->getGraphQlOperations() as $operationName => $operation) {
                if (null !== $operation->getDescription()) {
                    continue;
                }

                $graphQlOperations[$operationName] = $operation->withDescription($description);
            }

            $resourceMetadataCollection[$key] = $resourceMetadataCollection[$key]->withGraphQlOperations($graphQlOperations);
        }

        return $resourceMetadataCollection;
    }

    /**
     * Gets the short description of the class.
     */
    private function getShortDescription(string $class): ?string
    {
        if (!$docBlock = $this->getDocBlock($class)) {
            return null;
        }

        foreach ($docBlock->children as $docChild) {
            if ($docChild instanceof PhpDocTextNode && !empty($docChild->text)) {
                return $docChild->text;
            }
        }

        return null;
    }

    private function getDocBlock(string $class): ?PhpDocNode
    {
        if (isset($this->docBlocks[$class])) {
            return $this->docBlocks[$class];
        }

        if (!$this->phpDocParser || !$this->lexer) {
            return null;
        }

        try {
            $reflectionClass = new \ReflectionClass($class);
        } catch (\ReflectionException) {
            return null;
        }

        $rawDocNode = $reflectionClass->getDocComment();
        if (!$rawDocNode) {
            return null;
        }

        $tokens = new TokenIterator($this->lexer->tokenize($rawDocNode));
        $phpDocNode = $this->phpDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $this->docBlocks[$class] = $phpDocNode;
    }
}

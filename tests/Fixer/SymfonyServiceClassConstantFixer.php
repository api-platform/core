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

namespace ApiPlatform\Tests\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class SymfonyServiceClassConstantFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Converts fully qualified class names in Symfony service definitions to ::class constants and adds import statements.',
            [
                new CodeSample(
                    "<?php\nreturn static function (ContainerConfigurator \$container) {\n    \$services = \$container->services();\n    \$services->set('my_service', 'App\\Service\\MyService');\n};\n"
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ApiPlatform/symfony_service_class_constant';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_OBJECT_OPERATOR) && $tokens->isTokenKindFound(\T_CONSTANT_ENCAPSED_STRING);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        $useDeclarations = (new NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens);
        $existingImports = [];
        foreach ($useDeclarations as $useDeclaration) {
            $existingImports[$useDeclaration->getShortName()] = $useDeclaration->getFullName();
        }

        $newImports = [];

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(\T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }

            $content = trim($token->getContent(), "'\"");
            if (!str_contains($content, '\\')) {
                continue;
            }

            // Check for ->set()
            if ($this->isSetCall($tokens, $index)) {
                $this->replaceWithClassConstant($tokens, $index, $content, $existingImports, $newImports);
                continue;
            }

            // Check for inline_service()
            if ($this->isInlineServiceCall($tokens, $index)) {
                $this->replaceWithClassConstant($tokens, $index, $content, $existingImports, $newImports);
                continue;
            }

            // Check for alias()
            if ($this->isAliasCall($tokens, $index)) {
                $this->replaceWithClassConstant($tokens, $index, $content, $existingImports, $newImports);
                continue;
            }
        }

        if (!empty($newImports)) {
            $this->insertImports($tokens, $newImports);
        }
    }

    // Helper to check if the current token is part of a ->set() call
    private function isSetCall(Tokens $tokens, int $index): bool
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$prevIndex]->equals(',')) {
            return false;
        }

        $firstArgIndex = $tokens->getPrevMeaningfulToken($prevIndex);
        if (!$tokens[$firstArgIndex]->isGivenKind(\T_CONSTANT_ENCAPSED_STRING)) {
            return false;
        }

        $parenIndex = $tokens->getPrevMeaningfulToken($firstArgIndex);
        if (!$tokens[$parenIndex]->equals('(')) {
            return false;
        }

        $setMethodIndex = $tokens->getPrevMeaningfulToken($parenIndex);
        $setMethodToken = $tokens[$setMethodIndex];
        if (!$setMethodToken->isGivenKind(\T_STRING) || 'set' !== strtolower($setMethodToken->getContent())) {
            return false;
        }

        $arrowIndex = $tokens->getPrevMeaningfulToken($setMethodIndex);
        if (!$tokens[$arrowIndex]->isGivenKind(\T_OBJECT_OPERATOR)) {
            return false;
        }

        return true;
    }

    // Helper to check if the current token is part of an inline_service() call
    private function isInlineServiceCall(Tokens $tokens, int $index): bool
    {
        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$prevIndex]->equals('(')) {
            return false;
        }

        $functionIndex = $tokens->getPrevMeaningfulToken($prevIndex);
        if (!$tokens[$functionIndex]->isGivenKind(\T_STRING) || 'inline_service' !== $tokens[$functionIndex]->getContent()) {
            return false;
        }

        return true;
    }

    // Helper to check if the current token is part of an alias() call
    private function isAliasCall(Tokens $tokens, int $index): bool
    {
        // Check if the current token is a string (potential FQCN)
        if (!$tokens[$index]->isGivenKind(\T_CONSTANT_ENCAPSED_STRING)) {
            return false;
        }

        // Check if the previous meaningful token is an opening parenthesis
        $prevIndex = $tokens->getPrevMeaningfulToken($index);
        if (!$tokens[$prevIndex]->equals('(')) {
            return false;
        }

        // Check if the token before the parenthesis is the 'alias' method
        $aliasMethodIndex = $tokens->getPrevMeaningfulToken($prevIndex);
        if (!$tokens[$aliasMethodIndex]->isGivenKind(\T_STRING) || 'alias' !== $tokens[$aliasMethodIndex]->getContent()) {
            return false;
        }

        // Check if the token before 'alias' is the object operator (->)
        $arrowIndex = $tokens->getPrevMeaningfulToken($aliasMethodIndex);
        if (!$tokens[$arrowIndex]->isGivenKind(\T_OBJECT_OPERATOR)) {
            return false;
        }

        return true;
    }

    private function replaceWithClassConstant(
        Tokens $tokens,
        int $index,
        string $content,
        array $existingImports,
        array &$newImports,
    ): void {
        $fqcn = ltrim($content, '\\');
        $shortName = $this->getShortName($fqcn);

        $replacementName = $fqcn;
        $needsImport = false;
        $alias = null;

        // Check for naming conflicts
        if (isset($existingImports[$shortName]) && $existingImports[$shortName] !== $fqcn) {
            $alias = $this->generateAlias($fqcn, $existingImports, $newImports);
            $replacementName = $alias;
            $needsImport = true;
            $newImports[$alias] = $fqcn;
        } elseif (isset($newImports[$shortName]) && $newImports[$shortName] !== $fqcn) {
            $alias = $this->generateAlias($fqcn, $existingImports, $newImports);
            $replacementName = $alias;
            $needsImport = true;
            $newImports[$alias] = $fqcn;
        } elseif (!isset($existingImports[$shortName]) && !isset($newImports[$shortName])) {
            $replacementName = $shortName;
            $needsImport = true;
            $newImports[$shortName] = $fqcn;
        } else {
            $replacementName = $shortName;
        }

        $nameTokens = $this->generateTokensForName($replacementName);
        $tokens[$index] = array_shift($nameTokens);
        $toInsert = $nameTokens;
        $toInsert[] = new Token([\T_DOUBLE_COLON, '::']);
        $toInsert[] = new Token([\T_STRING, 'class']);
        $tokens->insertAt($index + 1, $toInsert);
    }

    private function generateAlias(string $fqcn, array $existingImports, array $newImports): string
    {
        $parts = explode('\\', $fqcn);
        $shortName = end($parts);

        // Extract the parent namespace for aliasing
        $parentNamespace = $parts[\count($parts) - 2];

        // Use the parent namespace as a prefix for the alias
        $alias = $parentNamespace.$shortName;

        // Ensure the alias is unique
        $i = 1;
        while (isset($existingImports[$alias]) || isset($newImports[$alias])) {
            $alias = $parentNamespace.$shortName.$i;
            ++$i;
        }

        return $alias;
    }

    private function getShortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * Generates a list of tokens for a given class name, properly splitting namespaces.
     */
    private function generateTokensForName(string $name): array
    {
        $tokens = [];
        $parts = explode('\\', $name);
        foreach ($parts as $i => $part) {
            if ($i > 0) {
                $tokens[] = new Token([\T_NS_SEPARATOR, '\\']);
            }
            $tokens[] = new Token([\T_STRING, $part]);
        }

        return $tokens;
    }

    /**
     * Inserts use statements safely into the tokens collection.
     */
    private function insertImports(Tokens $tokens, array $imports): void
    {
        if (empty($imports)) {
            return;
        }

        asort($imports);

        $insertIndex = 0;

        // Find the correct position to insert the use statements
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(\T_DECLARE)) {
                $semicolon = $tokens->getNextTokenOfKind($index, [';']);
                if (null !== $semicolon) {
                    $insertIndex = $semicolon;
                }
                break;
            }
            if ($token->isGivenKind([\T_NAMESPACE, \T_USE, \T_CLASS, \T_FUNCTION, \T_RETURN])) {
                break;
            }
        }

        $namespaceIndex = $tokens->getNextTokenOfKind($insertIndex, [[\T_NAMESPACE]]);
        if (null !== $namespaceIndex) {
            $semicolon = $tokens->getNextTokenOfKind($namespaceIndex, [';']);
            if (null !== $semicolon) {
                $insertIndex = $semicolon;
            }
        }

        $scanIndex = $insertIndex;
        $maxIndex = $tokens->count();

        while ($scanIndex < $maxIndex) {
            $token = $tokens[$scanIndex];
            if ($token->isGivenKind([\T_CLASS, \T_FUNCTION, \T_RETURN])) {
                break;
            }
            if ($token->isGivenKind(\T_USE)) {
                $next = $tokens->getNextMeaningfulToken($scanIndex);
                if ($tokens[$next]->equals('(')) {
                    ++$scanIndex;
                    continue;
                }
                $semicolon = $tokens->getNextTokenOfKind($scanIndex, [';']);
                if (null !== $semicolon) {
                    $insertIndex = $semicolon;
                    $scanIndex = $semicolon;
                }
            }
            ++$scanIndex;
        }

        $tokensToInsert = [];
        $tokensToInsert[] = new Token([\T_WHITESPACE, "\n"]);

        foreach ($imports as $alias => $fqcn) {
            $tokensToInsert[] = new Token([\T_USE, 'use']);
            $tokensToInsert[] = new Token([\T_WHITESPACE, ' ']);

            $nameTokens = $this->generateTokensForName($fqcn);
            foreach ($nameTokens as $t) {
                $tokensToInsert[] = $t;
            }

            if ($alias !== $this->getShortName($fqcn)) {
                $tokensToInsert[] = new Token([\T_WHITESPACE, ' ']);
                $tokensToInsert[] = new Token([\T_AS, 'as']);
                $tokensToInsert[] = new Token([\T_WHITESPACE, ' ']);
                $tokensToInsert[] = new Token([\T_STRING, $alias]);
            }

            $tokensToInsert[] = new Token(';');
            $tokensToInsert[] = new Token([\T_WHITESPACE, "\n"]);
        }

        $tokens->insertAt($insertIndex + 1, $tokensToInsert);
    }
}

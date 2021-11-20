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

use PhpCsFixer\Fixer\CastNotation\NoUnsetCastFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Comment\MultilineCommentOpeningClosingFixer;
use PhpCsFixer\Fixer\ControlStructure\NoAlternativeSyntaxFixer;
use PhpCsFixer\Fixer\ControlStructure\NoSuperfluousElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationArrayAssignmentFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationSpacesFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\ExplicitIndirectVariableFixer;
use PhpCsFixer\Fixer\LanguageConstruct\NoUnsetOnPropertyFixer;
use PhpCsFixer\Fixer\Operator\LogicalOperatorsFixer;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoAliasTagFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarAnnotationCorrectOrderFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitSetUpTearDownVisibilityFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\CompactNullableTypehintFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\RuleSet\Sets\PHP71MigrationRiskySet;
use PhpCsFixer\RuleSet\Sets\PHP71MigrationSet;
use PhpCsFixer\RuleSet\Sets\PHPUnit60MigrationRiskySet;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

$header = <<<'HEADER'
This file is part of the API Platform project.

(c) Kévin Dunglas <dunglas@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

return static function (ContainerConfigurator $containerConfigurator) use ($header): void {
    $services = $containerConfigurator->services();

    $containerConfigurator->import(SetList::DOCTRINE_ANNOTATIONS);
    $services->set(PHP71MigrationSet::class);
    $services->set(PHP71MigrationRiskySet::class);
    $services->set(PHPUnit60MigrationRiskySet::class);
    $containerConfigurator->import(SetList::SYMFONY);
    $containerConfigurator->import(SetList::SYMFONY_RISKY);

    $services->set(AlignMultilineCommentFixer::class)
        ->call('configure', [[
            'comment_type' => 'phpdocs_like',
        ]]);
    $services->set(ArrayIndentationFixer::class);
    $services->set(CompactNullableTypehintFixer::class);
    $services->set(DoctrineAnnotationArrayAssignmentFixer::class)
        ->call('configure', [[
            'operator' => '=',
        ]]);
    $services->set(DoctrineAnnotationSpacesFixer::class)
        ->call('configure', [[
            'after_array_assignments_equals' => false,
            'before_array_assignments_equals' => false,
        ]]);
    $services->set(ExplicitIndirectVariableFixer::class);
    $services->set(FullyQualifiedStrictTypesFixer::class);
    $services->set(HeaderCommentFixer::class)
        ->call('configure', [[
            'header' => $header,
            'location' => 'after_open',
        ]]);
    $services->set(LogicalOperatorsFixer::class);
    $services->set(MultilineCommentOpeningClosingFixer::class);
    $services->set(MultilineWhitespaceBeforeSemicolonsFixer::class)
        ->call('configure', [[
            'strategy' => 'no_multi_line',
        ]]);
    $services->set(NoAlternativeSyntaxFixer::class);
    $services->set(NoExtraBlankLinesFixer::class)
        ->call('configure', [[
            'tokens' => [
                'break',
                'continue',
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
            ],
        ]]);
    $services->set(NoSuperfluousElseifFixer::class);
    // To re-enable in API Platform 3: https://github.com/symfony/symfony/issues/43021
    //$services->set(NoSuperfluousPhpdocTagsFixer::class)
    //    ->call('configure', [[
    //        'allow_mixed' => false,
    //    ]])
    //;
    $services->set(NoUnsetCastFixer::class);
    $services->set(NoUnsetOnPropertyFixer::class);
    $services->set(NoUselessElseFixer::class);
    $services->set(NoUselessReturnFixer::class);
    $services->set(OrderedImportsFixer::class)
        ->call('configure', [[
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha',
        ]]);
    $services->set(PhpUnitMethodCasingFixer::class)
        ->call('configure', [[
            'case' => 'camel_case',
        ]]);
    $services->set(PhpUnitSetUpTearDownVisibilityFixer::class);
    $services->set(PhpUnitTestAnnotationFixer::class)
        ->call('configure', [[
            'style' => 'prefix',
        ]]);
    $services->set(PhpdocAddMissingParamAnnotationFixer::class)
        ->call('configure', [[
            'only_untyped' => true,
        ]]);
    $services->set(PhpdocNoAliasTagFixer::class);
    $services->set(PhpdocOrderFixer::class);
    $services->set(PhpdocTrimConsecutiveBlankLineSeparationFixer::class);
    $services->set(PhpdocVarAnnotationCorrectOrderFixer::class);
    $services->set(ReturnAssignmentFixer::class);
    $services->set(StrictParamFixer::class);
    $services->set(VisibilityRequiredFixer::class)
        ->call('configure', [[
            'elements' => [
                'const',
                'method',
                'property',
            ],
        ]]);
    // BC breaks; to be done in API Platform 3.0
    $services->remove(VoidReturnFixer::class);

    $parameters = $containerConfigurator->parameters();
    $parameters
        ->set(Option::PATHS, [__DIR__])
        ->set(Option::SKIP, [
            __DIR__.'/src/Core/Bridge/Symfony/Maker/Resources/skeleton',
            __DIR__.'/tests/Fixtures/app/var',
            __DIR__.'src/Bridge/Symfony/Bundle/DependencyInjection/Configuration.php',
            __DIR__.'src/Annotation/ApiFilter.php', // temporary
            __DIR__.'src/Annotation/ApiProperty.php', // temporary
            __DIR__.'src/Annotation/ApiResource.php', // temporary
            __DIR__.'src/Annotation/ApiSubresource.php', // temporary
            __DIR__.'tests/Fixtures/TestBundle/Entity/DummyPhp8.php', // temporary
        ]);
};

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

namespace ApiPlatform\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Component\Finder\Finder;

/**
 * Behat coverage.
 *
 * @author eliecharra
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @copyright Adapted from https://gist.github.com/eliecharra/9c8b3ba57998b50e14a6
 */
final class CoverageContext implements Context
{
    /**
     * @var CodeCoverage
     */
    private static $coverage;

    /**
     * @BeforeSuite
     */
    public static function setup(): void
    {
        $filter = new Filter();
        $finder =
            (new Finder())
            ->in(__DIR__.'/../../src')
            ->exclude([
                'src/Core/Bridge/Symfony/Maker/Resources/skeleton',
                'tests/Fixtures/app/var',
                'docs/guides',
                'docs/var',
                'src/Doctrine/Orm/Tests/var',
                'src/Doctrine/Odm/Tests/var',
            ])
            ->append([
                'tests/Fixtures/app/console',
            ])
            ->files()
            ->name('*.php');

        foreach ($finder as $file) {
            $filter->includeFile((string) $file);
        }

        self::$coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
    }

    /**
     * @AfterSuite
     */
    public static function teardown(): void
    {
        $feature = getenv('FEATURE') ?: 'behat';
        (new PHP())->process(self::$coverage, __DIR__."/../../build/coverage/coverage-$feature.cov");
    }

    /**
     * @BeforeScenario
     */
    public function before(BeforeScenarioScope $scope): void
    {
        self::$coverage->start("{$scope->getFeature()->getTitle()}::{$scope->getScenario()->getTitle()}");
    }

    /**
     * @AfterScenario
     */
    public function after(): void
    {
        self::$coverage->stop();
    }
}

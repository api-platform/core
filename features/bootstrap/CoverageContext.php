<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Clover;

/**
 * Behat coverage.
 *
 * @author eliecharra
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @copyright Adapted from https://gist.github.com/eliecharra/9c8b3ba57998b50e14a6
 */
class CoverageContext implements Context
{
    /**
     * @var CodeCoverage
     */
    private static $coverage;

    /**
     * @BeforeSuite
     */
    public static function setup()
    {
        $filter = new Filter();
        $filter->addDirectoryToWhitelist(__DIR__.'/../../src');
        self::$coverage = new CodeCoverage(null, $filter);
    }

    /**
     * @AfterSuite
     */
    public static function tearDown()
    {
        $writer = new Clover();
        $writer->process(self::$coverage, __DIR__.'/../../clover-behat.xml');
    }

    private function getCoverageKeyFromScope(BeforeScenarioScope $scope) : string
    {
        return sprintf('%s::%s', $scope->getFeature()->getTitle(), $scope->getScenario()->getTitle());
    }

    /**
     * @BeforeScenario
     */
    public function startCoverage(BeforeScenarioScope $scope)
    {
        self::$coverage->start($this->getCoverageKeyFromScope($scope));
    }

    /**
     * @AfterScenario
     */
    public function stopCoverage()
    {
        self::$coverage->stop();
    }
}

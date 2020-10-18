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

namespace ApiPlatform\Core\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prediction\PredictionException;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

/**
 * Copied and adapted from phpspec/prophecy-phpunit.
 * To replace by the official package when we'll drop support for PHP 7.1.
 *
 * @see https://github.com/phpspec/prophecy-phpunit
 *
 * @copyright Christophe Coevoet <stof@notk.org>
 * @author Christophe Coevoet <stof@notk.org>
 * @mixin TestCase
 */
trait ProphecyTrait
{
    /**
     * @var Prophet|null
     *
     * @internal
     */
    private $prophet;

    /**
     * @var bool
     *
     * @internal
     */
    private $prophecyAssertionsCounted = false;

    /**
     * @psalm-param class-string|null $classOrInterface
     *
     * @param mixed|null $classOrInterface
     *
     * @throws DoubleException
     * @throws InterfaceNotFoundException
     */
    protected function prophesize($classOrInterface = null): ObjectProphecy
    {
        if (
            \is_string($classOrInterface) &&
            \is_callable([$this, 'recordDoubledType']) // Support for PHPUnit 7
        ) {
            \assert($this instanceof TestCase);
            $this->recordDoubledType($classOrInterface);
        }

        return $this->getProphet()->prophesize($classOrInterface);
    }

    /**
     * @postCondition
     * @after
     */
    protected function verifyProphecyDoubles(): void
    {
        if (null === $this->prophet) {
            return;
        }

        try {
            $this->prophet->checkPredictions();
        } catch (PredictionException $e) {
            throw new AssertionFailedError($e->getMessage());
        } finally {
            $this->countProphecyAssertions();
        }
    }

    /**
     * @after
     */
    protected function tearDownProphecy(): void
    {
        if (null !== $this->prophet && !$this->prophecyAssertionsCounted) {
            // Some Prophecy assertions may have been done in tests themselves even when a failure happened before checking mock objects.
            $this->countProphecyAssertions();
        }

        $this->prophet = null;
    }

    /**
     * @internal
     */
    private function countProphecyAssertions(): void
    {
        \assert($this instanceof TestCase);
        $this->prophecyAssertionsCounted = true;

        foreach ($this->prophet->getProphecies() as $objectProphecy) {
            /** @var MethodProphecy[] $methodProphecies */
            foreach ($objectProphecy->getMethodProphecies() as $methodProphecies) {
                foreach ($methodProphecies as $methodProphecy) {
                    \assert($methodProphecy instanceof MethodProphecy);

                    $this->addToAssertionCount(\count($methodProphecy->getCheckedPredictions()));
                }
            }
        }
    }

    /**
     * @internal
     */
    private function getProphet(): Prophet
    {
        if (null === $this->prophet) {
            $this->prophet = new Prophet();
        }

        return $this->prophet;
    }
}

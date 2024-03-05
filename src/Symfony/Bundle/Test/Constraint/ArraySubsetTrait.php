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

namespace ApiPlatform\Symfony\Bundle\Test\Constraint;

use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Exporter\Exporter;

/**
 * Constraint that asserts that the array it is evaluated for has a specified subset.
 *
 * Uses array_replace_recursive() to check if a key value subset is part of the
 * subject array.
 *
 * Imported from dms/phpunit-arraysubset-asserts, because the original constraint has been deprecated.
 *
 * @copyright Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright Rafael Dohms <rdohms@gmail.com>
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/3494
 */
trait ArraySubsetTrait
{
    public function __construct(private iterable $subset, private readonly bool $strict = false)
    {
    }

    private function _evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        // type cast $other & $this->subset as an array to allow
        // support in standard array functions.
        $other = $this->toArray($other);
        $this->subset = $this->toArray($this->subset);
        $patched = array_replace_recursive($other, $this->subset);
        if ($this->strict) {
            $result = $other === $patched;
        } else {
            $result = $other == $patched;
        }
        if ($returnResult) {
            return $result;
        }
        if ($result) {
            return null;
        }

        $f = new ComparisonFailure(
            $patched,
            $other,
            var_export($patched, true),
            var_export($other, true)
        );
        $this->fail($other, $description, $f);
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'has the subset '.(new Exporter())->export($this->subset);
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other): string
    {
        return 'an array '.$this->toString();
    }

    private function toArray(iterable $other): array
    {
        if (\is_array($other)) {
            return $other;
        }
        if ($other instanceof \ArrayObject) {
            return $other->getArrayCopy();
        }

        return iterator_to_array($other);
    }
}

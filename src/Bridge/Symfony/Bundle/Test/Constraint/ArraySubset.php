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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

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
final class ArraySubset extends Constraint
{
    private $subset;
    private $strict;

    public function __construct(iterable $subset, bool $strict = false)
    {
        $this->strict = $strict;
        $this->subset = $subset;
    }

    /**
     * Evaluates the constraint for parameter $other.
     *
     * If $returnResult is set to false (the default), an exception is thrown
     * in case of a failure. null is returned otherwise.
     *
     * If $returnResult is true, the result of the evaluation is returned as
     * a boolean value instead: true in case of success, false in case of a
     * failure.
     *
     * @param iterable $other
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     *
     * @return iterable|bool|null
     */
    public function evaluate($other, string $description = '', bool $returnResult = false)
    {
        //type cast $other & $this->subset as an array to allow
        //support in standard array functions.
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
     * Returns a string representation of the constraint.
     *
     * @throws InvalidArgumentException
     */
    public function toString(): string
    {
        return 'has the subset '.$this->exporter()->export($this->subset);
    }

    /**
     * Returns the description of the failure.
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param mixed $other evaluated value or object
     *
     * @throws InvalidArgumentException
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
        if ($other instanceof \Traversable) {
            return iterator_to_array($other);
        }
        // Keep BC even if we know that array would not be the expected one
        return (array) $other;
    }
}

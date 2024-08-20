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

namespace ApiPlatform\Laravel\Test;

use ApiPlatform\Laravel\Test\Constraint\ArraySubset;
use ApiPlatform\Metadata\IriConverterInterface;
use PHPUnit\Framework\ExpectationFailedException;

trait ApiTestAssertionsTrait
{
    /**
     * Asserts that an array has a specified subset.
     *
     * Imported from dms/phpunit-arraysubset, because the original constraint has been deprecated.
     *
     * @copyright Sebastian Bergmann <sebastian@phpunit.de>
     * @copyright Rafael Dohms <rdohms@gmail.com>
     *
     * @see https://github.com/sebastianbergmann/phpunit/issues/3494
     *
     * @param array<mixed, mixed> $subset
     * @param array<mixed, mixed> $array
     *
     * @throws ExpectationFailedException
     * @throws \Exception
     */
    public static function assertArraySubset(iterable $subset, iterable $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        $constraint = new ArraySubset($subset, $checkForObjectIdentity);

        static::assertThat($array, $constraint, $message);
    }

    /**
     * Asserts that the retrieved JSON contains the specified subset.
     *
     * This method delegates to static::assertArraySubset().
     *
     * @param array<mixed, mixed> $subset
     * @param array<mixed, mixed> $json
     */
    public static function assertJsonContains(array|string $subset, array $json, bool $checkForObjectIdentity = true, string $message = ''): void
    {
        if (\is_string($subset)) {
            $subset = json_decode($subset, true, 512, \JSON_THROW_ON_ERROR);
        }
        if (!\is_array($subset)) {
            throw new \InvalidArgumentException('$subset must be array or string (JSON array or JSON object)');
        }

        static::assertArraySubset($subset, $json, $checkForObjectIdentity, $message);
    }

    /**
     * Generates the IRI of a resource item.
     */
    protected function getIriFromResource(object $resource): ?string
    {
        $iriConverter = $this->app->make(IriConverterInterface::class);

        return $iriConverter->getIriFromResource($resource);
    }
}

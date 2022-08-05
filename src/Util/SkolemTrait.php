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

namespace ApiPlatform\Util;

/**
 * Generates a Skolem IRI.
 *
 * @internal
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
trait SkolemTrait
{
    /**
     * @param object $object
     */
    private function generateSkolemIri($object): string
    {
        return '/.well-known/genid/'.spl_object_hash($object);
    }
}

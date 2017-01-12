<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use Doctrine\Common\Persistence\ObjectManager;

interface IdentifierManagerInterface
{
    /**
     * Transform and check the identifier, composite or not.
     *
     * @param int|string    $id
     * @param ObjectManager $manager
     * @param string        $resourceClass
     *
     * @throws PropertyNotFoundException
     *
     * @return array
     */
    public function normalizeIdentifiers($id, ObjectManager $manager, string $resourceClass): array;
}

<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

/**
 * Class representing an API resource able to specify its item and collection route name.
 *
 * @author Jérémy Hubert <jeremy@tofusteak.fr>
 */
interface RoutedResourceInterface extends ResourceInterface
{
    /**
     * Gets the item route name of the resource (for @id generation).
     *
     * @return string|null
     */
    public function getItemRouteName();

    /**
     * Gets the collection route name of the resource (for @id generation).
     *
     * @return string|null
     */
    public function getCollectionRouteName();
}

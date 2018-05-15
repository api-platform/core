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

namespace ApiPlatform\Core\DataPersister;

/**
 * Manages data persistence.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
interface DataPersisterInterface
{
    /**
     * Is the data supported by the persister?
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function supports($data): bool;

    /**
     * Persists the data.
     *
     * @param mixed $data
     */
    public function persist($data);

    /**
     * Removes the data.
     *
     * @param mixed $data
     */
    public function remove($data);
}

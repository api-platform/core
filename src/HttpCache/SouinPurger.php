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

namespace ApiPlatform\HttpCache;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Purges Souin.
 *
 * @author Sylvain Combraque <darkweak@protonmail.com>
 */
class SouinPurger extends SurrogateKeysPurger
{
    private const MAX_HEADER_SIZE_PER_BATCH = 1500;
    private const SEPARATOR = ', ';
    private const HEADER = 'Surrogate-Key';

    /**
     * @param HttpClientInterface[] $clients
     */
    public function __construct(iterable $clients)
    {
        parent::__construct($clients, self::MAX_HEADER_SIZE_PER_BATCH, self::HEADER, self::SEPARATOR);
    }
}

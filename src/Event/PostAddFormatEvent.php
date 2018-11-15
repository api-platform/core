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

namespace ApiPlatform\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class PostAddFormatEvent extends Event
{
    const NAME = ApiPlatformEvents::POST_ADD_FORMAT;
    private $formats;

    public function __construct($formats)
    {
        $this->formats = $formats;
    }

    public function getFormats()
    {
        return $this->formats;
    }

    public function setFormats($formats): void
    {
        $this->formats = $formats;
    }
}

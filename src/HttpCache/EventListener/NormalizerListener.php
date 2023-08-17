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

namespace ApiPlatform\HttpCache\EventListener;

use ApiPlatform\Serializer\NormalizeItemEvent;

/**
 * Collects cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
class NormalizerListener
{
    public function onPreNormalizeItem(NormalizeItemEvent $event): void
    {
        $this->addResourceToContext($event);
    }

    public function onNormalizeRelation(NormalizeItemEvent $event): void
    {
        $this->addResourceToContext($event);
    }

    public function onJsonApiNormalizeRelation(NormalizeItemEvent $event): void
    {
        $this->addResourceToContext($event);
    }

    private function addResourceToContext(NormalizeItemEvent $event): void
    {
        if (isset($event->context['resources'])) {
            $event->context['resources'][$event->iri] = $event->iri;
        }
    }
}

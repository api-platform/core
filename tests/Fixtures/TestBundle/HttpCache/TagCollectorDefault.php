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

namespace ApiPlatform\Tests\Fixtures\TestBundle\HttpCache;

use ApiPlatform\Serializer\TagCollectorInterface;

/**
 * Collects cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
class TagCollectorDefault implements TagCollectorInterface
{
    public function collect(array $context = []): void
    {
        if (!isset($context['property_metadata'])) {
            $this->addResourceToContext($context);
        }
    }

    private function addResourceToContext(array $context): void
    {
        $iri = $context['iri'];

        if (isset($context['resources']) && isset($iri)) {
            $context['resources'][$iri] = $iri;
        }
    }
}

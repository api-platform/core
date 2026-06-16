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

namespace ApiPlatform\Tests\Fixtures\TestBundle\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

/**
 * Registers the reusable "401" response component referenced by the
 * Issue8143\ReferenceResponse fixture so the emitted $ref resolves.
 */
final class Issue8143ResponseComponentFactory implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $components = $openApi->getComponents();
        $responses = $components->getResponses() ?? new \ArrayObject();

        if (!isset($responses['401'])) {
            $responses['401'] = new Response('Unauthorized');
        }

        return $openApi->withComponents($components->withResponses($responses));
    }
}

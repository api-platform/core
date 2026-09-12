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

namespace ApiPlatform\Laravel\Controller;

use ApiPlatform\Documentation\ApiCatalogFactory;
use ApiPlatform\State\Util\JsonLinksetSerializer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the API catalog document at the "api-catalog" well-known URI.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9727.html
 *
 * @author Florent Morselli <florent.morselli@spomky-labs.com>
 */
final class ApiCatalogController
{
    public function __construct(
        private readonly ApiCatalogFactory $apiCatalogFactory,
        private readonly JsonLinksetSerializer $serializer = new JsonLinksetSerializer(),
    ) {
    }

    public function __invoke(): Response
    {
        $links = $this->apiCatalogFactory->create();

        $headers = [
            'Content-Type' => \sprintf('application/linkset+json; profile="%s"', ApiCatalogFactory::PROFILE),
            // RFC 9727, section 2: a HEAD request is answered with the link relation of section 3
            'Link' => \sprintf('<%s>; rel="api-catalog"', $this->apiCatalogFactory->getUrl()),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return new Response($this->serializer->serialize($links, \JSON_UNESCAPED_SLASHES), Response::HTTP_OK, $headers);
    }
}

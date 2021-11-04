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

namespace ApiPlatform\Tests\Fixtures\TestBundle\MessengerHandler;

use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MessengerResponseInput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MessengerWithResponseHandler implements MessageHandlerInterface
{
    public function __invoke(MessengerResponseInput $data)
    {
        $response = new Response();
        $response->setContent(json_encode([
            'data' => 123,
        ]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}

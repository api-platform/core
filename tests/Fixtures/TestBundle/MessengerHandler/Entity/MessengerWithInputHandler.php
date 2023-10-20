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

namespace ApiPlatform\Tests\Fixtures\TestBundle\MessengerHandler\Entity;

use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MessengerInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MessengerWithInput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

if (\PHP_VERSION_ID >= 80000 && class_exists(AsMessageHandler::class)) {
    #[AsMessageHandler]
    class MessengerWithInputHandler
    {
        public function __invoke(MessengerInput $data)
        {
            $object = new MessengerWithInput();
            $object->name = 'test';
            $object->id = 1;

            return $object;
        }
    }
} else {
    class MessengerWithInputHandler implements MessageHandlerInterface
    {
        public function __invoke(MessengerInput $data)
        {
            $object = new MessengerWithInput();
            $object->name = 'test';
            $object->id = 1;

            return $object;
        }
    }
}

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

namespace ApiPlatform\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Psr\Container\ContainerInterface;

/**
 * Context for Mercure.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MercureContext implements Context
{
    public function __construct(private readonly ContainerInterface $driverContainer)
    {
    }

    /**
     * @Then the following Mercure update with topics :topics should have been sent:
     */
    public function theFollowingMercureUpdateShouldHaveBeenSent(string $topics, PyStringNode $update): void
    {
        $topics = explode(',', $topics);
        $update = json_decode($update->getRaw(), true, 512, \JSON_THROW_ON_ERROR);

        $updateHandler = $this->driverContainer->get('mercure.hub.default.message_handler');

        foreach ($updateHandler->getUpdates() as $sentUpdate) {
            $toMatchTopics = \count($topics);
            foreach ($sentUpdate->getTopics() as $sentTopic) {
                foreach ($topics as $topic) {
                    if (preg_match("@$topic@", (string) $sentTopic)) {
                        --$toMatchTopics;
                    }
                }
            }

            if ($toMatchTopics > 0) {
                continue;
            }

            if ($sentUpdate->getData() === json_encode($update, \JSON_THROW_ON_ERROR)) {
                return;
            }
        }

        throw new \RuntimeException('Mercure update has not been sent.');
    }
}

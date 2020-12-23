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

use ApiPlatform\Core\Tests\Fixtures\DummyMercurePublisher;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Mercure\Update;

/**
 * Context for Mercure.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MercureContext implements Context
{
    private $publisher;

    public function __construct(DummyMercurePublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @Then the following Mercure update with topics :topics should have been sent:
     */
    public function theFollowingMercureUpdateShouldHaveBeenSent(string $topics, PyStringNode $update): void
    {
        $topics = explode(',', $topics);
        $update = json_decode($update->getRaw(), true);

        /** @var Update $sentUpdate */
        foreach ($this->publisher->getUpdates() as $sentUpdate) {
            $toMatchTopics = count($topics);
            foreach ($sentUpdate->getTopics() as $sentTopic) {
                foreach ($topics as $topic) {
                    if (preg_match("@$topic@", $sentTopic)) {
                        --$toMatchTopics;
                    }
                }
            }

            if ($toMatchTopics > 0) {
                continue;
            }

            if ($sentUpdate->getData() === json_encode($update)) {
                return;
            }
        }

        throw new \RuntimeException('Mercure update has not been sent.');
    }
}

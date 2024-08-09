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

use ApiPlatform\Tests\Fixtures\TestBundle\Mercure\TestHub;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mercure\Update;

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
     * @Then :number Mercure updates should have been sent
     * @Then :number Mercure update should have been sent
     */
    public function mercureUpdatesShouldHaveBeenSent(int $number): void
    {
        $updateHandler = $this->getMercureTestHub();
        $total = \count($updateHandler->getUpdates());

        if (0 === $total) {
            throw new \RuntimeException('No Mercure update has been sent.');
        }

        Assert::assertEquals($number, $total, \sprintf('Expected %d Mercure updates to be sent, got %d.', $number, $total));
    }

    /**
     * @Then the first Mercure update should have topics:
     * @Then the Mercure update should have topics:
     */
    public function firstMercureUpdateShouldHaveTopics(TableNode $table): void
    {
        $this->mercureUpdateShouldHaveTopics(1, $table);
    }

    /**
     * @Then the first Mercure update should have data:
     * @Then the Mercure update should have data:
     */
    public function firstMercureUpdateShouldHaveData(PyStringNode $data): void
    {
        $this->mercureUpdateShouldHaveData(1, $data);
    }

    /**
     * @Then the Mercure update number :index should have topics:
     */
    public function mercureUpdateShouldHaveTopics(int $index, TableNode $table): void
    {
        $updateHandler = $this->getMercureTestHub();
        $updates = $updateHandler->getUpdates();

        if (0 === \count($updates)) {
            throw new \RuntimeException('No Mercure update has been sent.');
        }

        if (!isset($updates[$index - 1])) {
            throw new \RuntimeException(\sprintf('Mercure update #%d does not exist.', $index));
        }
        /** @var Update $update */
        $update = $updates[$index - 1];
        Assert::assertEquals(array_keys($table->getRowsHash()), array_values($update->getTopics()));
    }

    /**
     * @Then the Mercure update number :index should have data:
     */
    public function mercureUpdateShouldHaveData(int $index, PyStringNode $data): void
    {
        $updateHandler = $this->getMercureTestHub();
        $updates = $updateHandler->getUpdates();

        if (0 === \count($updates)) {
            throw new \RuntimeException('No Mercure update has been sent.');
        }

        if (!isset($updates[$index - 1])) {
            throw new \RuntimeException(\sprintf('Mercure update #%d does not exist.', $index));
        }
        /** @var Update $update */
        $update = $updates[$index - 1];
        Assert::assertJsonStringEqualsJsonString($data->getRaw(), $update->getData());
    }

    /**
     * @Then the following Mercure update with topics :topics should have been sent:
     */
    public function theFollowingMercureUpdateShouldHaveBeenSent(string $topics, PyStringNode $update): void
    {
        $topics = explode(',', $topics);
        $update = json_decode($update->getRaw(), true, 512, \JSON_THROW_ON_ERROR);

        $updateHandler = $this->getMercureTestHub();
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

    private function getMercureTestHub(): TestHub
    {
        return $this->driverContainer->get('mercure.hub.default.test_hub');
    }
}

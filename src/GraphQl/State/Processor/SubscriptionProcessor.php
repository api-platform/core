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

namespace ApiPlatform\GraphQl\State\Processor;

use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\OperationAwareSubscriptionManagerInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

/**
 * Adds the mercure subscription url if available.
 */
final class SubscriptionProcessor implements ProcessorInterface
{
    public function __construct(private readonly ProcessorInterface $decorated, private readonly SubscriptionManagerInterface $subscriptionManager, private readonly ?MercureSubscriptionIriGeneratorInterface $mercureSubscriptionIriGenerator)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $data = $this->decorated->process($data, $operation, $uriVariables, $context);
        if (!$operation instanceof GraphQlOperation || !($mercure = $operation->getMercure())) {
            return $data;
        }

        if ($this->subscriptionManager instanceof OperationAwareSubscriptionManagerInterface) {
            $subscriptionId = $this->subscriptionManager->retrieveSubscriptionId($context, $data, $operation);
        } else {
            $subscriptionId = $this->subscriptionManager->retrieveSubscriptionId($context, $data);
        }

        if ($subscriptionId) {
            if (!$this->mercureSubscriptionIriGenerator) {
                throw new \LogicException('Cannot use Mercure for subscriptions when MercureBundle is not installed. Try running "composer require mercure".');
            }

            $hub = \is_array($mercure) ? ($mercure['hub'] ?? null) : null;
            $data['mercureUrl'] = $this->mercureSubscriptionIriGenerator->generateMercureUrl($subscriptionId, $hub);
        }

        return $data;
    }
}

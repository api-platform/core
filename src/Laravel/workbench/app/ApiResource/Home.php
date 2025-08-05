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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Annotation\Groups;
use Workbench\App\Models\Order;
use Workbench\Database\Factories\OrderFactory;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/home',
            normalizationContext: ['groups' => ['home:read']],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class Home
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    #[Groups(['home:read'])]
    public ?Order $order = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $order = OrderFactory::new()->create();
        $home = new self();
        $home->order = $order;

        return $home;
    }
}

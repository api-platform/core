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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\TransformedDummyEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/transformed_dummy_entity_ressources'),
        new Get(uriTemplate: '/transformed_dummy_entity_ressources/{id}'),
        new Post(uriTemplate: '/transformed_dummy_entity_ressources'),
        new Patch(uriTemplate: '/transformed_dummy_entity_ressources/{id}'),
    ],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
    stateOptions: new Options(
        entityClass: TransformedDummyEntity::class,
        transformFromEntity: TransformedDummyEntityRessourceTransformer::class,
        transformToEntity: TransformedDummyEntityRessourceTransformer::class,
    ),
)]
class TransformedDummyEntityRessource
{
    #[Groups(['read'])]
    public ?int $id = null;

    #[Groups(['read', 'write'])]
    public ?int $year = null;

    public ?TransformedDummyEntity $entityRef = null;
}

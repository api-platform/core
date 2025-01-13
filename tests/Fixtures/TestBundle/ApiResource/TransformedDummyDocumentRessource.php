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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\TransformedDummyDocument;

#[ApiResource(
    operations : [
        new GetCollection(uriTemplate: '/transformed_dummy_document_ressources'),
        new Get(uriTemplate: '/transformed_dummy_document_ressources/{id}'),
    ],
    stateOptions: new \ApiPlatform\Doctrine\Odm\State\Options(
        documentClass: TransformedDummyDocument::class,
        transformFromDocument: [self::class, 'transformToResource'],
    )
)]
class TransformedDummyDocumentRessource
{
    public ?int $id = null;

    public ?int $year = null;

    public static function transformToResource(TransformedDummyDocument $model): self
    {
        $resource = new self();
        $resource->id = $model->getId();
        $resource->year = (int) $model->getDate()->format('Y');

        return $resource;
    }
}

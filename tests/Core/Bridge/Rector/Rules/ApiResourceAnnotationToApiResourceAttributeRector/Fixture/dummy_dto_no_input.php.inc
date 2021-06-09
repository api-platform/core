<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\CreateItemAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\DoubleBatAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;

/**
 * @ApiResource(
 *     attributes={
 *         "input"=false,
 *         "output"=OutputDto::class
 *     },
 *     collectionOperations={
 *         "post"={
 *             "method"="POST",
 *             "path"="/dummy_dto_no_inputs",
 *             "controller"=CreateItemAction::class
 *         },
 *         "get"
 *     },
 *     itemOperations={
 *         "get",
 *         "delete",
 *         "post_double_bat"={
 *             "method"="POST",
 *             "path"="/dummy_dto_no_inputs/{id}/double_bat",
 *             "controller"=DoubleBatAction::class,
 *             "status"=200
 *         }
 *     }
 * )
 */
class DummyDtoNoInput
{
}

?>
-----
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\CreateItemAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\DoubleBatAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\OutputDto;

#[ApiResource(input: false, output: OutputDto::class)]
#[Get]
#[Delete]
#[Post(name: 'post_double_bat', uriTemplate: '/dummy_dto_no_inputs/{id}/double_bat', controller: DoubleBatAction::class, status: 200)]
#[Post(uriTemplate: '/dummy_dto_no_inputs', controller: CreateItemAction::class)]
#[GetCollection]
class DummyDtoNoInput
{
}

?>

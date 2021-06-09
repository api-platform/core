<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(itemOperations={
 *     "get",
 *     "get_custom"={"method"="GET", "path"="custom_action_collection_dummies/{id}"},
 *     "custom_normalization"={"route_name"="custom_normalization", "method"="GET"},
 *     "short_custom_normalization"={"route_name"="short_custom_normalization", "method"="GET"}
 * },
 * collectionOperations={
 *     "get",
 *     "get_custom"={"method"="GET", "path"="custom_action_collection_dummies"},
 *     "custom_denormalization"={"route_name"="custom_denormalization", "method"="GET"},
 *     "short_custom_denormalization"={"route_name"="short_custom_denormalization", "method"="GET"}
 * })
 */
class CustomActionDummy
{
}

?>
-----
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[Get]
#[Get(name: 'get_custom', uriTemplate: 'custom_action_collection_dummies/{id}')]
#[Get(name: 'custom_normalization', routeName: 'custom_normalization')]
#[Get(name: 'short_custom_normalization', routeName: 'short_custom_normalization')]
#[GetCollection]
#[GetCollection(name: 'get_custom', uriTemplate: 'custom_action_collection_dummies')]
#[GetCollection(name: 'custom_denormalization', routeName: 'custom_denormalization')]
#[GetCollection(name: 'short_custom_denormalization', routeName: 'short_custom_denormalization')]
class CustomActionDummy
{
}

?>

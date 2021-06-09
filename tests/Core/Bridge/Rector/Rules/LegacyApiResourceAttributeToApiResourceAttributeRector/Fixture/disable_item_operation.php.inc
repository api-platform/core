<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(collectionOperations: ['get'], itemOperations: ['get' => ['controller' => NotFoundAction::class, 'read' => false, 'output' => false]])]
class DisableItemOperation
{
}

?>
-----
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[Get(controller: NotFoundAction::class, read: false, output: false)]
#[GetCollection]
class DisableItemOperation
{
}

?>

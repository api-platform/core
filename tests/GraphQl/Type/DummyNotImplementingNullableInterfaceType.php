<?php declare(strict_types=1);

namespace ApiPlatform\Tests\GraphQl\Type;

use GraphQL\Type\Definition\Type;

class DummyNotImplementingNullableInterfaceType extends Type {

    public function __construct() {
        $this->name = 'Dummy';
    }

}

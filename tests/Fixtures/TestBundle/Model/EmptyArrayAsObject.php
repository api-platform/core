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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\State\EmptyArrayAsObjectProvider;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[ApiResource(operations: [new Get()], provider: EmptyArrayAsObjectProvider::class)]
class EmptyArrayAsObject
{
    public int $id = 6;

    public array $emptyArray = [];

    #[Context([Serializer::EMPTY_ARRAY_AS_OBJECT => true, AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true])]
    public array $emptyArrayAsObject = [];

    public \ArrayObject $arrayObjectAsArray;

    #[Context([AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true])]
    public \ArrayObject $arrayObject;

    public array $stringArray = ['foo', 'bar'];

    public array $objectArray = ['foo' => 67, 'bar' => 'baz'];

    public function __construct()
    {
        $this->arrayObjectAsArray = new \ArrayObject();
        $this->arrayObject = new \ArrayObject();
    }
}

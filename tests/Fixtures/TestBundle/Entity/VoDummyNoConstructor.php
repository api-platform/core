<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

class VoDummyNoConstructor
{
    use VoDummyIdAwareTrait;

    public function setId(int $id): VoDummyNoConstructor
    {
        $this->id = $id;
        return $this;
    }
}

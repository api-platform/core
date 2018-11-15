<?php


namespace ApiPlatform\Core\Event;


use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\EventDispatcher\Event;

class PreRespondEvent extends Event
{
    const NAME = 'api_platform.pre_respond';

    protected $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function getResourceMetadataFactory(): ResourceMetadataFactoryInterface
    {
        return $this->resourceMetadataFactory;
    }
}

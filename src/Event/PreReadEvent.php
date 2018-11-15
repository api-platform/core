<?php

namespace ApiPlatform\Core\Event;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\EventDispatcher\Event;

class PreReadEvent extends Event
{
    const NAME = 'api_platform.pre_read';

    /** @var CollectionDataProviderInterface */
    protected $collectionDataProvider;
    /** @var ItemDataProviderInterface */
    protected $itemDataProvider;
    /** @var SubresourceDataProviderInterface */
    protected $subresourceDataProvider;
    /** @var SerializerContextBuilderInterface */
    protected $serializerContextBuilder;
    /** @var IdentifierConverterInterface */
    protected $identifierConverter;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, ItemDataProviderInterface $itemDataProvider, SubresourceDataProviderInterface $subresourceDataProvider = null, SerializerContextBuilderInterface $serializerContextBuilder = null, IdentifierConverterInterface $identifierConverter = null)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->itemDataProvider = $itemDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->identifierConverter = $identifierConverter;
    }

    public function getSerializerContextBuilder(): SerializerContextBuilderInterface
    {
        return $this->serializerContextBuilder;
    }

    public function getCollectionDataProvider(): CollectionDataProviderInterface
    {
        return $this->collectionDataProvider;
    }

    public function getIdentifierConverter(): IdentifierConverterInterface
    {
        return $this->identifierConverter;
    }

    public function getItemDataProvider(): ItemDataProviderInterface
    {
        return $this->itemDataProvider;
    }

    public function getSubresourceDataProvider(): SubresourceDataProviderInterface
    {
        return $this->subresourceDataProvider;
    }
}

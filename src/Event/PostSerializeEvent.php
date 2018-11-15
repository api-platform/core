<?php


namespace ApiPlatform\Core\Event;


use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Serializer\SerializerInterface;

class PostSerializeEvent extends Event
{
    const NAME = 'api_platform.post_serialize';

    /** @var SerializerInterface */
    protected $serializer;
    /** @var SerializerContextBuilderInterface */
    protected $serializerContextBuilder;

    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function getSerializerContextBuilder(): SerializerContextBuilderInterface
    {
        return $this->serializerContextBuilder;
    }
}

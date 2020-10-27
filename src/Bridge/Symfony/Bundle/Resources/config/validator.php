<?php


use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory;
use ApiPlatform\Core\Bridge\Symfony\Validator\Validator;
use ApiPlatform\Core\Filter\QueryParameterValidateListener;
use ApiPlatform\Core\Validator\EventListener\ValidateListener;
use ApiPlatform\Core\Validator\ValidatorInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.validator', Validator::class)
            ->args([service('validator'), service('service_container'), ])
        ->alias(ValidatorInterface::class, 'api_platform.validator')
        ->set('api_platform.metadata.property.metadata_factory.validator', ValidatorPropertyMetadataFactory::class)
            ->decorate('api_platform.metadata.property.metadata_factory', null, 20)
            ->args([service('validator'), service('api_platform.metadata.property.metadata_factory.validator.inner'), ])
        ->set('api_platform.listener.view.validate', ValidateListener::class)
            ->args([service('api_platform.validator'), service('api_platform.metadata.resource.metadata_factory'), ])
            ->tag('kernel.event_listener', ['event' => 'kernel.view','method' => 'onKernelView','priority' => 64,])
        ->set('api_platform.listener.view.validate_query_parameters', QueryParameterValidateListener::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), service('api_platform.filter_locator'), ])
            ->tag('kernel.event_listener', ['event' => 'kernel.request','method' => 'onKernelRequest','priority' => 16,])
    ;
};

<?php


use ApiPlatform\Core\Problem\Serializer\ConstraintViolationListNormalizer;
use ApiPlatform\Core\Problem\Serializer\ErrorNormalizer;
use ApiPlatform\Core\Serializer\JsonEncoder;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.problem.encoder', JsonEncoder::class)
            ->args(['jsonproblem', ])
            ->tag('serializer.encoder')
        ->set('api_platform.problem.normalizer.constraint_violation_list', ConstraintViolationListNormalizer::class)
            ->args([param('api_platform.validator.serialize_payload_fields'), service('api_platform.name_converter')->ignoreOnInvalid, ])
            ->tag('serializer.normalizer', ['priority' => -780,])
        ->set('api_platform.problem.normalizer.error', ErrorNormalizer::class)
            ->args([param('kernel.debug'), ])
            ->tag('serializer.normalizer', ['priority' => -810,])
    ;
};

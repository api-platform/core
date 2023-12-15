<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\RuleBuilders\Architecture\Architecture;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');
    $config->add($classSet, ...Architecture::withComponents()
        ->component('Api')->definedBy('ApiPlatform\Api\*')
        ->component('DoctrineCommon')->definedBy('ApiPlatform\Doctrine\Common\*')
        ->component('Documentation')->definedBy('ApiPlatform\Documentation\*')
        ->component('Elasticsearch')->definedBy('ApiPlatform\Elasticsearch\*')
        ->component('GraphQl')->definedBy('ApiPlatform\GraphQl\*')
        ->component('HttpCache')->definedBy('ApiPlatform\HttpCache\*')
        ->component('Hydra')->definedBy('ApiPlatform\Hydra\*')
        ->component('JsonLd')->definedBy('ApiPlatform\JsonLd\*')
        ->component('JsonSchema')->definedBy('ApiPlatform\JsonSchema\*')
        ->component('Metadata')->definedBy('ApiPlatform\Metadata\*')
        ->component('OpenApi')->definedBy('ApiPlatform\OpenApi\*')
        ->component('RamseyUuid')->definedBy('ApiPlatform\RamseyUuid\*')
        ->component('Serializer')->definedBy('ApiPlatform\Serializer\*')
        ->component('State')->definedBy('ApiPlatform\State\*')
        ->component('Symfony')->definedBy('ApiPlatform\Symfony\*')
        ->component('Validator')->definedBy('ApiPlatform\Validator\*')

        ->where('DoctrineCommon')->mayDependOnComponents('Metadata', 'State')
        ->where('Documentation')->mayDependOnComponents('Metadata', 'OpenApi', 'State')
        ->where('Elasticsearch')->mayDependOnComponents('Metadata', 'Serializer', 'State')
        ->where('GraphQl')->mayDependOnComponents('Metadata', 'Serializer', 'State', 'Validator')
        ->where('HttpCache')->mayDependOnComponents('Metadata', 'State')
        ->where('Hydra')->mayDependOnComponents('Metadata', 'State', 'JsonLd', 'Serializer', 'JsonSchema')
        ->where('JsonLd')->mayDependOnComponents('Metadata', 'State', 'Serializer')
        ->where('JsonSchema')->mayDependOnComponents('Metadata')
        ->where('OpenApi')->mayDependOnComponents('JsonSchema', 'Metadata', 'State')
        ->where('RamseyUuid')->mayDependOnComponents('Metadata')
        ->where('Serializer')->mayDependOnComponents('Metadata', 'State')
        ->where('Symfony')->mayDependOnComponents(
            'Documentation',
            'GraphQl',
            'Metadata',
            'State',
            'Validator',
            'Serializer',
            'JsonSchema',
            'JsonLd',
            'OpenApi',
            'HttpCache',
            'Elasticsearch'
        )
        ->where('Validator')->mayDependOnComponents('Metadata')

        ->rules()
    );
};

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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.metadata.property.metadata_factory.validator', 'ApiPlatform\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory')
        ->decorate('api_platform.metadata.property.metadata_factory', null, 20)
        ->args([
            service('validator'),
            service('api_platform.metadata.property.metadata_factory.validator.inner'),
            tagged_iterator('api_platform.metadata.property_schema_restriction'),
        ]);

    $services->set('api_platform.metadata.property_schema.choice_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaChoiceRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.collection_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCollectionRestriction')
        ->args([tagged_iterator('api_platform.metadata.property_schema_restriction')])
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.count_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCountRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.css_color_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCssColorRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.greater_than_or_equal_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanOrEqualRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.greater_than_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.length_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.less_than_or_equal_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanOrEqualRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.less_than_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.one_of_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaOneOfRestriction')
        ->args([tagged_iterator('api_platform.metadata.property_schema_restriction')])
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.range_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRangeRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.regex_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.format_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat')
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.unique_restriction', 'ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaUniqueRestriction')
        ->tag('api_platform.metadata.property_schema_restriction');
};

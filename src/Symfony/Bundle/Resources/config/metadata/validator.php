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

use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaChoiceRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCollectionRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCountRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCssColorRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanOrEqualRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanOrEqualRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaOneOfRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRangeRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaUniqueRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.metadata.property.metadata_factory.validator', ValidatorPropertyMetadataFactory::class)
        ->decorate('api_platform.metadata.property.metadata_factory', null, 20)
        ->args([
            service('validator'),
            service('api_platform.metadata.property.metadata_factory.validator.inner'),
            tagged_iterator('api_platform.metadata.property_schema_restriction'),
        ]);

    $services->set('api_platform.metadata.property_schema.choice_restriction', PropertySchemaChoiceRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.collection_restriction', PropertySchemaCollectionRestriction::class)
        ->args([tagged_iterator('api_platform.metadata.property_schema_restriction')])
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.count_restriction', PropertySchemaCountRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.css_color_restriction', PropertySchemaCssColorRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.greater_than_or_equal_restriction', PropertySchemaGreaterThanOrEqualRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.greater_than_restriction', PropertySchemaGreaterThanRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.length_restriction', PropertySchemaLengthRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.less_than_or_equal_restriction', PropertySchemaLessThanOrEqualRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.less_than_restriction', PropertySchemaLessThanRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.one_of_restriction', PropertySchemaOneOfRestriction::class)
        ->args([tagged_iterator('api_platform.metadata.property_schema_restriction')])
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.range_restriction', PropertySchemaRangeRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.regex_restriction', PropertySchemaRegexRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.format_restriction', PropertySchemaFormat::class)
        ->tag('api_platform.metadata.property_schema_restriction');

    $services->set('api_platform.metadata.property_schema.unique_restriction', PropertySchemaUniqueRestriction::class)
        ->tag('api_platform.metadata.property_schema_restriction');
};

<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\State\\\\\\\\ProcessorInterface\' and ApiPlatform\\\\Doctrine\\\\Common\\\\State\\\\PersistProcessor will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Doctrine/Common/Tests/State/PersistProcessorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\State\\\\\\\\ProcessorInterface\' and ApiPlatform\\\\Doctrine\\\\Common\\\\State\\\\RemoveProcessor will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Doctrine/Common/Tests/State/RemoveProcessorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with Doctrine\\\\ODM\\\\MongoDB\\\\Configuration and \'setMetadataCache\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Doctrine/Odm/Tests/DoctrineMongoDbOdmSetup.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Doctrine\\\\\\\\Orm\\\\\\\\Extension\\\\\\\\QueryResultCollectionExtensionInterface\' and ApiPlatform\\\\Doctrine\\\\Orm\\\\Extension\\\\PaginationExtension will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Doctrine/Orm/Tests/Extension/PaginationExtensionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertTrue\\(\\) with true will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Doctrine/Orm/Tests/State/CollectionProviderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertTrue\\(\\) with true will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Doctrine/Orm/Tests/State/ItemProviderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Exception\\\\\\\\ExceptionInterface\' and ApiPlatform\\\\Elasticsearch\\\\Exception\\\\IndexNotFoundException will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Exception/IndexNotFoundExceptionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Throwable\' and ApiPlatform\\\\Elasticsearch\\\\Exception\\\\IndexNotFoundException will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Exception/IndexNotFoundExceptionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Exception\\\\\\\\NonUniqueIdentifierException\' and ApiPlatform\\\\Elasticsearch\\\\Exception\\\\NonUniqueIdentifierException will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Exception/NonUniqueIdentifierExceptionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Throwable\' and ApiPlatform\\\\Elasticsearch\\\\Exception\\\\NonUniqueIdentifierException will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Exception/NonUniqueIdentifierExceptionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Extension\\\\\\\\RequestBodySearchCollectionExtensionInterface\' and ApiPlatform\\\\Elasticsearch\\\\Extension\\\\ConstantScoreFilterExtension will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Extension/ConstantScoreFilterExtensionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Extension\\\\\\\\RequestBodySearchCollectionExtensionInterface\' and ApiPlatform\\\\Elasticsearch\\\\Extension\\\\SortExtension will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Extension/SortExtensionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Extension\\\\\\\\RequestBodySearchCollectionExtensionInterface\' and ApiPlatform\\\\Elasticsearch\\\\Extension\\\\SortFilterExtension will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Extension/SortFilterExtensionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Filter\\\\\\\\ConstantScoreFilterInterface\' and ApiPlatform\\\\Elasticsearch\\\\Filter\\\\MatchFilter will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Filter/MatchFilterTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Filter\\\\\\\\SortFilterInterface\' and ApiPlatform\\\\Elasticsearch\\\\Filter\\\\OrderFilter will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Filter/OrderFilterTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Elasticsearch\\\\\\\\Filter\\\\\\\\ConstantScoreFilterInterface\' and ApiPlatform\\\\Elasticsearch\\\\Filter\\\\TermFilter will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Filter/TermFilterTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Metadata\\\\\\\\Resource\\\\\\\\Factory\\\\\\\\ResourceMetadataCollectionFactoryInterface\' and ApiPlatform\\\\Elasticsearch\\\\Metadata\\\\Resource\\\\Factory\\\\ElasticsearchProviderResourceMetadataCollectionFactory will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Metadata/Resource/Factory/ElasticsearchProviderResourceMetadataCollectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\State\\\\\\\\Pagination\\\\\\\\PaginatorInterface\' and ApiPlatform\\\\State\\\\Pagination\\\\PaginatorInterface will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/PaginatorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\Normalizer\\\\\\\\DenormalizerInterface\' and ApiPlatform\\\\Elasticsearch\\\\Serializer\\\\DocumentNormalizer will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Serializer/DocumentNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\Normalizer\\\\\\\\NormalizerInterface\' and ApiPlatform\\\\Elasticsearch\\\\Serializer\\\\DocumentNormalizer will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Serializer/DocumentNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\Normalizer\\\\\\\\DenormalizerInterface\' and ApiPlatform\\\\Elasticsearch\\\\Serializer\\\\ItemNormalizer will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Serializer/ItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\Normalizer\\\\\\\\NormalizerInterface\' and ApiPlatform\\\\Elasticsearch\\\\Serializer\\\\ItemNormalizer will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Serializer/ItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\SerializerAwareInterface\' and ApiPlatform\\\\Elasticsearch\\\\Serializer\\\\ItemNormalizer will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Serializer/ItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\NameConverter\\\\\\\\AdvancedNameConverterInterface\' and ApiPlatform\\\\Elasticsearch\\\\Serializer\\\\NameConverter\\\\InnerFieldsNameConverter will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Elasticsearch/Tests/Serializer/NameConverter/InnerFieldsNameConverterTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\HttpFoundation\\\\\\\\Response\' and Symfony\\\\Component\\\\HttpFoundation\\\\Response will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Action/EntrypointActionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\HttpFoundation\\\\\\\\Response\' and Symfony\\\\Component\\\\HttpFoundation\\\\Response will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Action/GraphQlPlaygroundActionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\HttpFoundation\\\\\\\\Response\' and Symfony\\\\Component\\\\HttpFoundation\\\\Response will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Action/GraphiQlActionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\GraphQl\\\\\\\\Tests\\\\\\\\Fixtures\\\\\\\\ApiResource\\\\\\\\Dummy\' and ApiPlatform\\\\GraphQl\\\\Tests\\\\Fixtures\\\\ApiResource\\\\Dummy will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Serializer/ItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function assert\\(\\) with true will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Serializer/SerializerContextBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Instanceof between ApiPlatform\\\\Metadata\\\\GraphQl\\\\Mutation\\|ApiPlatform\\\\Metadata\\\\GraphQl\\\\Query\\|ApiPlatform\\\\Metadata\\\\GraphQl\\\\Subscription and ApiPlatform\\\\Metadata\\\\GraphQl\\\\Operation will always evaluate to true\\.$#',
	'identifier' => 'instanceof.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Serializer/SerializerContextBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\:\\:with\\(\\) invoked with named argument \\$normalization, but it\'s not allowed because of @no\\-named\\-arguments\\.$#',
	'identifier' => 'argument.named',
	'count' => 2,
	'path' => __DIR__ . '/src/GraphQl/Tests/State/Processor/NormalizeProcessorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method PHPUnit\\\\Framework\\\\MockObject\\\\Builder\\\\InvocationMocker\\:\\:with\\(\\) invoked with named argument \\$normalization, but it\'s not allowed because of @no\\-named\\-arguments\\.$#',
	'identifier' => 'argument.named',
	'count' => 4,
	'path' => __DIR__ . '/src/GraphQl/Tests/State/Provider/DenormalizeProviderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Operation is not subtype of native type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Query\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/FieldsBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertArrayHasKey\\(\\) with \'fields\' and array\\{name\\?\\: string\\|null, description\\?\\: string\\|null, fields\\: \\(callable\\(\\)\\: iterable\\<array\\{name\\?\\: string, type\\: callable\\(\\)\\: \\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\), defaultValue\\?\\: mixed, description\\?\\: string\\|null, deprecationReason\\?\\: string\\|null, astNode\\?\\: GraphQL\\\\Language\\\\AST\\\\InputValueDefinitionNode\\|null\\}\\|callable\\(\\)\\: \\(array\\{name\\?\\: string, type\\: callable\\(\\)\\: \\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\), defaultValue\\?\\: mixed, description\\?\\: string\\|null, deprecationReason\\?\\: string\\|null, astNode\\?\\: GraphQL\\\\Language\\\\AST\\\\InputValueDefinitionNode\\|null\\}\\|GraphQL\\\\Type\\\\Definition\\\\InputObjectField\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\)\\|GraphQL\\\\Type\\\\Definition\\\\InputObjectField\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\>\\)\\|iterable\\<array\\{name\\?\\: string, type\\: \\(callable\\(\\)\\: \\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\)\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\), defaultValue\\?\\: mixed, description\\?\\: string\\|null, deprecationReason\\?\\: string\\|null, astNode\\?\\: GraphQL\\\\Language\\\\AST\\\\InputValueDefinitionNode\\|null\\}\\|\\(callable\\(\\)\\: \\(array\\{name\\?\\: string, type\\: callable\\(\\)\\: \\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\), defaultValue\\?\\: mixed, description\\?\\: string\\|null, deprecationReason\\?\\: string\\|null, astNode\\?\\: GraphQL\\\\Language\\\\AST\\\\InputValueDefinitionNode\\|null\\}\\|GraphQL\\\\Type\\\\Definition\\\\InputObjectField\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\)\\)\\|GraphQL\\\\Type\\\\Definition\\\\InputObjectField\\|\\(GraphQL\\\\Type\\\\Definition\\\\InputType&GraphQL\\\\Type\\\\Definition\\\\Type\\)\\>, parseValue\\?\\: callable\\(array\\<string, mixed\\>\\)\\: mixed, astNode\\?\\: GraphQL\\\\Language\\\\AST\\\\InputObjectTypeDefinitionNode\\|null, extensionASTNodes\\?\\: array\\<GraphQL\\\\Language\\\\AST\\\\InputObjectTypeExtensionNode\\>\\|null\\} will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 4,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'GraphQL\\\\\\\\Type\\\\\\\\Definition\\\\\\\\InputObjectType\' and GraphQL\\\\Type\\\\Definition\\\\InputObjectType will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 4,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'GraphQL\\\\\\\\Type\\\\\\\\Definition\\\\\\\\NonNull\' and GraphQL\\\\Type\\\\Definition\\\\NonNull will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Operation is not subtype of native type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Mutation\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 6,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Operation is not subtype of native type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Query\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 4,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Operation is not subtype of native type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Subscription\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 3,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type GraphQL\\\\Type\\\\Definition\\\\Type is not subtype of native type null\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeBuilderTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Operation is not subtype of native type ApiPlatform\\\\Metadata\\\\GraphQl\\\\Query\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 8,
	'path' => __DIR__ . '/src/GraphQl/Tests/Type/TypeConverterTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type Symfony\\\\Contracts\\\\HttpClient\\\\HttpClientInterface is not subtype of native type GuzzleHttp\\\\ClientInterface@anonymous/src/HttpCache/Tests/SouinPurgerTest\\.php\\:105\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/HttpCache/Tests/SouinPurgerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type Symfony\\\\Contracts\\\\HttpClient\\\\HttpClientInterface is not subtype of native type GuzzleHttp\\\\ClientInterface@anonymous/src/HttpCache/Tests/SouinPurgerTest\\.php\\:136\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/HttpCache/Tests/SouinPurgerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type Symfony\\\\Contracts\\\\HttpClient\\\\HttpClientInterface is not subtype of native type GuzzleHttp\\\\ClientInterface@anonymous/src/HttpCache/Tests/SouinPurgerTest\\.php\\:63\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/HttpCache/Tests/SouinPurgerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type Symfony\\\\Contracts\\\\HttpClient\\\\HttpClientInterface is not subtype of native type GuzzleHttp\\\\ClientInterface@anonymous/src/HttpCache/Tests/VarnishPurgerTest\\.php\\:75\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/HttpCache/Tests/VarnishPurgerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type Symfony\\\\Contracts\\\\HttpClient\\\\HttpClientInterface is not subtype of native type GuzzleHttp\\\\ClientInterface@anonymous/src/HttpCache/Tests/VarnishXKeyPurgerTest\\.php\\:105\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/HttpCache/Tests/VarnishXKeyPurgerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\Serializer\' and \'getSupportedTypes\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Hydra/Tests/Serializer/CollectionFiltersNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Serializer\\\\\\\\Serializer\' and \'getSupportedTypes\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Hydra/Tests/Serializer/PartialCollectionViewNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\JsonApi\\\\\\\\Tests\\\\\\\\Fixtures\\\\\\\\Dummy\' and ApiPlatform\\\\JsonApi\\\\Tests\\\\Fixtures\\\\Dummy will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/JsonApi/Tests/Serializer/ItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function method_exists\\(\\) with Symfony\\\\Component\\\\Serializer\\\\Normalizer\\\\NormalizerInterface and \'getSupportedTypes\' will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/JsonLd/Serializer/ErrorNormalizer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ArrayObject\' and ApiPlatform\\\\JsonSchema\\\\Schema will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/JsonSchema/Tests/SchemaTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Metadata\\\\\\\\ApiProperty\' and ApiPlatform\\\\Metadata\\\\ApiProperty will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/src/Metadata/Tests/Property/Factory/SerializerPropertyMetadataFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Countable\' and ApiPlatform\\\\Metadata\\\\Property\\\\PropertyNameCollection will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Metadata/Tests/Property/PropertyNameCollectionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'IteratorAggregate\' and ApiPlatform\\\\Metadata\\\\Property\\\\PropertyNameCollection will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Metadata/Tests/Property/PropertyNameCollectionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method ApiPlatform\\\\Metadata\\\\FilterInterface@anonymous/src/Metadata/Tests/Resource/Factory/ParameterResourceMetadataCollectionFactoryTest\\.php\\:41\\:\\:getDescription\\(\\) should return array\\<string, array\\{property\\?\\: string, type\\?\\: string, required\\?\\: bool, description\\?\\: string, strategy\\?\\: string, is_collection\\?\\: bool, openapi\\?\\: ApiPlatform\\\\OpenApi\\\\Model\\\\Parameter, schema\\?\\: array\\<string, mixed\\>\\}\\> but returns array\\{hydra\\: array\\{property\\: \'hydra\', type\\: \'string\', required\\: false, schema\\: array\\{type\\: \'foo\'\\}, openapi\\: ApiPlatform\\\\OpenApi\\\\Model\\\\Parameter\\}, everywhere\\: array\\{property\\: \'everywhere\', type\\: \'string\', required\\: false, openapi\\: array\\{allowEmptyValue\\: true\\}\\}\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Metadata/Tests/Resource/Factory/ParameterResourceMetadataCollectionFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Metadata\\\\\\\\Operation\' and ApiPlatform\\\\Metadata\\\\Operation will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Metadata/Tests/Resource/OperationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\OpenApi\\\\\\\\Model\\\\\\\\Components\' and ApiPlatform\\\\OpenApi\\\\Model\\\\Components will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/OpenApi/Tests/Factory/OpenApiFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\OpenApi\\\\\\\\OpenApi\' and ApiPlatform\\\\OpenApi\\\\OpenApi will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/OpenApi/Tests/Factory/OpenApiFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Serializer\\\\\\\\Tests\\\\\\\\Fixtures\\\\\\\\ApiResource\\\\\\\\DtoWithNullValue\' and ApiPlatform\\\\Serializer\\\\Tests\\\\Fixtures\\\\ApiResource\\\\DtoWithNullValue will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Serializer/Tests/AbstractItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Serializer\\\\\\\\Tests\\\\\\\\Fixtures\\\\\\\\ApiResource\\\\\\\\Dummy\' and ApiPlatform\\\\Serializer\\\\Tests\\\\Fixtures\\\\ApiResource\\\\Dummy will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 5,
	'path' => __DIR__ . '/src/Serializer/Tests/AbstractItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Serializer\\\\\\\\Tests\\\\\\\\Fixtures\\\\\\\\ApiResource\\\\\\\\NonCloneableDummy\' and ApiPlatform\\\\Serializer\\\\Tests\\\\Fixtures\\\\ApiResource\\\\NonCloneableDummy will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Serializer/Tests/AbstractItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Serializer\\\\\\\\Tests\\\\\\\\Fixtures\\\\\\\\ApiResource\\\\\\\\SecuredDummy\' and ApiPlatform\\\\Serializer\\\\Tests\\\\Fixtures\\\\ApiResource\\\\SecuredDummy will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 5,
	'path' => __DIR__ . '/src/Serializer/Tests/AbstractItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Serializer\\\\\\\\Tests\\\\\\\\ObjectWithBasicProperties\' and ApiPlatform\\\\Serializer\\\\Tests\\\\ObjectWithBasicProperties will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Serializer/Tests/AbstractItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Serializer\\\\\\\\Tests\\\\\\\\Fixtures\\\\\\\\ApiResource\\\\\\\\Dummy\' and ApiPlatform\\\\Serializer\\\\Tests\\\\Fixtures\\\\ApiResource\\\\Dummy will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 5,
	'path' => __DIR__ . '/src/Serializer/Tests/ItemNormalizerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ArrayObject\' and ApiPlatform\\\\State\\\\ResourceList will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/State/Tests/ResourceListTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$changeSet of class Doctrine\\\\ORM\\\\Event\\\\PreUpdateEventArgs constructor expects array\\<string, array\\{mixed, mixed\\}\\|Doctrine\\\\ORM\\\\PersistentCollection\\>, array\\{lorem\\: \'ipsum\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Symfony/Tests/Doctrine/EventListener/PurgeHttpCacheListenerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'ApiPlatform\\\\\\\\Metadata\\\\\\\\Exception\\\\\\\\RuntimeException\' and ApiPlatform\\\\Validator\\\\Exception\\\\ValidationException will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Validator/Tests/Exception/ValidationExceptionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'RuntimeException\' and ApiPlatform\\\\Validator\\\\Exception\\\\ValidationException will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/src/Validator/Tests/Exception/ValidationExceptionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Service "behat\\.driver\\.service_container" is not registered in the container\\.$#',
	'identifier' => 'symfonyContainer.serviceNotFound',
	'count' => 1,
	'path' => __DIR__ . '/tests/Behat/HttpCacheContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Behat/JsonApiContext.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type iterable is not subtype of native type stdClass\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Behat/OpenApiContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Class ApiPlatform\\\\Tests\\\\Fixtures\\\\TestBundle\\\\ApiResource\\\\DtoOutput not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 8,
	'path' => __DIR__ . '/tests/JsonSchema/DefinitionNameFactoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type string is not subtype of native type \\(non\\-falsy\\-string\\|false\\)\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/tests/OpenApi/Command/OpenApiCommandTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type ApiPlatform\\\\State\\\\ProcessorInterface\\<string, Symfony\\\\Component\\\\HttpFoundation\\\\Response\\> is not subtype of native type ApiPlatform\\\\State\\\\Processor\\\\RespondProcessor\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 3,
	'path' => __DIR__ . '/tests/State/RespondProcessorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Property ApiPlatform\\\\Tests\\\\Symfony\\\\Bundle\\\\DataCollector\\\\RequestDataCollectorTest\\:\\:\\$attributes \\(Prophecy\\\\Prophecy\\\\ObjectProphecy\\|Symfony\\\\Component\\\\HttpFoundation\\\\ParameterBag\\) is never assigned Symfony\\\\Component\\\\HttpFoundation\\\\ParameterBag so it can be removed from the property type\\.$#',
	'identifier' => 'property.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DataCollector/RequestDataCollectorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Property ApiPlatform\\\\Tests\\\\Symfony\\\\Bundle\\\\DataCollector\\\\RequestDataCollectorTest\\:\\:\\$request \\(Prophecy\\\\Prophecy\\\\ObjectProphecy\\|Symfony\\\\Component\\\\HttpFoundation\\\\Request\\) is never assigned Symfony\\\\Component\\\\HttpFoundation\\\\Request so it can be removed from the property type\\.$#',
	'identifier' => 'property.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DataCollector/RequestDataCollectorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\AttributeFilterPass will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/AttributeFilterPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\AuthenticatorManagerPass will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/AuthenticatorManagerPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\ElasticsearchClientPass will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/ElasticsearchClientPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\FilterPass will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/FilterPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\GraphQlMutationResolverPass will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/GraphQlMutationResolverPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\GraphQlQueryResolverPass will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 2,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/GraphQlQueryResolverPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\GraphQlResolverPass will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/GraphQlResolverPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\GraphQlTypePass will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 3,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/GraphQlTypePassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\MetadataAwareNameConverterPass will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/MetadataAwareNameConverterPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\DependencyInjection\\\\\\\\Compiler\\\\\\\\CompilerPassInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Compiler\\\\TestClientPass will always evaluate to true\\.$#',
	'identifier' => 'staticMethod.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/Compiler/TestClientPassTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Config\\\\\\\\Definition\\\\\\\\Builder\\\\\\\\TreeBuilder\' and Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\TreeBuilder will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/ConfigurationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Config\\\\\\\\Definition\\\\\\\\ConfigurationInterface\' and ApiPlatform\\\\Symfony\\\\Bundle\\\\DependencyInjection\\\\Configuration will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/DependencyInjection/ConfigurationTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method ApiPlatform\\\\Symfony\\\\Bundle\\\\Test\\\\Client\\:\\:getKernelBrowser\\(\\) on a separate line has no effect\\.$#',
	'identifier' => 'method.resultUnused',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Bundle/Test/ClientTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Messenger\\\\\\\\Stamp\\\\\\\\StampInterface\' and ApiPlatform\\\\Symfony\\\\Messenger\\\\ContextStamp will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Messenger/ContextStampTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertIsArray\\(\\) with array will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Messenger/ContextStampTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertInstanceOf\\(\\) with \'Symfony\\\\\\\\Component\\\\\\\\Messenger\\\\\\\\Stamp\\\\\\\\StampInterface\' and ApiPlatform\\\\Symfony\\\\Messenger\\\\RemoveStamp will always evaluate to true\\.$#',
	'identifier' => 'method.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Symfony/Messenger/RemoveStampTest.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

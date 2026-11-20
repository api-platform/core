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

namespace ApiPlatform\Tests\Functional\JsonSchema;

use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8766\Issue8766Report;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;

final class JsonSchemaArrayShapeTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private SchemaFactoryInterface $schemaFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
    }

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Issue8766Report::class];
    }

    public function testArrayShapeDescribesItsKeys(): void
    {
        $this->assertEquals(new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'total' => ['type' => 'integer'],
                'processed' => ['type' => 'integer'],
                'failed' => ['type' => ['integer', 'null']],
            ],
            'required' => ['processed', 'total'],
            'additionalProperties' => false,
        ]), $this->properties()['totals']);
    }

    public function testUnsealedArrayShapeAllowsExtraKeys(): void
    {
        if (!Type::arrayShape([], false, Type::string(), Type::int())->getExtraValueType()?->isIdentifiedBy(TypeIdentifier::INT)) {
            $this->markTestSkipped('symfony/type-info < 7.4.6 returns the extra key type from ArrayShapeType::getExtraValueType().');
        }

        $this->assertEquals(new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'required' => ['name'],
            'additionalProperties' => ['type' => 'integer'],
        ]), $this->properties()['unsealed']);
    }

    public function testListArrayShapeStaysAnArray(): void
    {
        // symfony/type-info < 7.4.20 merges keyless items into one '' key, the tuple then falls back to a map
        $tuple = (new StringTypeResolver())->resolve('array{int, string}');
        if ($tuple instanceof ArrayShapeType && !array_is_list($tuple->getShape())) {
            $this->assertArrayNotHasKey('properties', $this->properties()['tuple']);

            return;
        }

        $this->assertEquals(new \ArrayObject([
            'type' => 'array',
            'items' => [
                'anyOf' => [
                    ['type' => 'integer'],
                    ['type' => 'string'],
                ],
            ],
        ]), $this->properties()['tuple']);
    }

    private function properties(): array
    {
        $schema = $this->schemaFactory->buildSchema(Issue8766Report::class, 'json');

        return (array) $schema->getDefinitions()['Issue8766Report']['properties'];
    }
}

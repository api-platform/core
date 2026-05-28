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

namespace ApiPlatform\Tests\Functional\Serializer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;

final class PropertyFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [DummyProperty::class, DummyGroup::class];
    }

    private static bool $fixturesLoaded = false;

    protected function loadFixtures(): void
    {
        if (self::$fixturesLoaded) {
            return;
        }
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM-only fixture; direct EntityManager persist of Entity\\DummyGroup is not portable to DocumentManager.');
        }
        self::createClient();
        $this->recreateSchema([DummyProperty::class, DummyGroup::class]);

        /** @var EntityManagerInterface $manager */
        $manager = $this->getManager();

        for ($i = 1; $i <= 10; ++$i) {
            $group = new DummyGroup();
            $property = new DummyProperty();

            foreach (['foo', 'bar', 'baz'] as $field) {
                $property->{$field} = $group->{$field} = ucfirst($field).' #'.$i;
            }
            $property->nameConverted = "NameConverted #{$i}";
            $property->group = $group;

            $manager->persist($group);
            $manager->persist($property);
        }
        $manager->flush();
        $manager->clear();
        self::$fixturesLoaded = true;
    }

    public static function tearDownAfterClass(): void
    {
        self::$fixturesLoaded = false;
        parent::tearDownAfterClass();
    }

    public function testGetACollectionOfResourcesByAttributesIdFooAndBar(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties?properties[]=id&properties[]=foo&properties[]=bar&properties[]=name_converted', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {},
              "@type": {},
              "id": {},
              "foo": {},
              "bar": {},
              "name_converted": {}
            },
            "additionalProperties": false,
            "required": ["@id", "@type", "id", "foo", "bar", "name_converted"]
          },
          "uniqueItems": true,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?properties%5B%5D=id&properties%5B%5D=foo&properties%5B%5D=bar&properties%5B%5D=name_converted&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByAttributesFooBarGroupBazAndGroupQux(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties?properties[]=foo&properties[]=bar&properties[group][]=baz&properties[group][]=qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {},
              "@type": {},
              "foo": {},
              "bar": {},
              "group": {
                "type": "object",
                "properties": {
                  "@id": {},
                  "@type": {},
                  "baz": {}
                },
                "additionalProperties": false,
                "required": ["@id", "@type", "baz"]
              }
            },
            "additionalProperties": false,
            "required": ["@id", "@type", "foo", "bar"]
          },
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?properties%5B%5D=foo&properties%5B%5D=bar&properties%5Bgroup%5D%5B%5D=baz&properties%5Bgroup%5D%5B%5D=qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByAttributesFooBar(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties?whitelisted_properties[]=foo&whitelisted_properties[]=bar&whitelisted_properties[]=name_converted', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {},
              "@type": {},
              "foo": {},
              "name_converted": {}
            },
            "additionalProperties": false,
            "required": ["@id", "@type", "foo", "name_converted"]
          },
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?whitelisted_properties%5B%5D=foo&whitelisted_properties%5B%5D=bar&whitelisted_properties%5B%5D=name_converted&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByWhitelistedNestedPropertiesFooBarAndGroupBaz(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties?whitelisted_nested_properties[]=foo&whitelisted_nested_properties[]=bar&whitelisted_nested_properties[group][]=baz', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {},
              "@type": {},
              "foo": {},
              "group": {
                "type": "object",
                "properties": {
                  "@id": {},
                  "@type": {},
                  "baz": {}
                },
                "additionalProperties": false,
                "required": ["@id", "@type", "baz"]
              }
            },
            "additionalProperties": false,
            "required": ["@id", "@type", "foo"]
          },
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?whitelisted_nested_properties%5B%5D=foo&whitelisted_nested_properties%5B%5D=bar&whitelisted_nested_properties%5Bgroup%5D%5B%5D=baz&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByAttributesBarNotAllowed(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties?whitelisted_properties[]=bar', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {},
              "@type": {}
            },
            "additionalProperties": false,
            "required": ["@id", "@type"]
          },
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?whitelisted_properties%5B%5D=bar&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByAttributesEmpty(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties?properties[]=&properties[group][]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@id": {},
              "@type": {},
              "group": {
                "type": "object",
                "properties": {
                  "@id": {},
                  "@type": {}
                },
                "additionalProperties": false,
                "required": ["@id", "@type"]
              }
            },
            "additionalProperties": false,
            "required": ["@id", "@type", "group"]
          },
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_properties\\?properties%5B%5D=&properties%5Bgroup%5D%5B%5D=&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetAResourceByAttributesIdFooAndBar(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties/1?properties[]=id&properties[]=foo&properties[]=bar', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties/1$"},
        "@type": {"pattern": "^DummyProperty$"},
        "id": {},
        "foo": {},
        "bar": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar"]
    }
JSON);
    }

    public function testGetAResourceByAttributesFooBarGroupBazAndGroupQux(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties/1?properties[]=foo&properties[]=bar&properties[group][]=baz&properties[group][]=qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties/1$"},
        "@type": {"pattern": "^DummyProperty$"},
        "foo": {},
        "bar": {},
        "group": {
          "type": "object",
          "properties": {
            "@id": {},
            "@type": {},
            "baz": {}
          },
          "additionalProperties": false,
          "required": ["@id", "@type", "baz"]
        }
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo", "bar", "group"]
    }
JSON);
    }

    public function testGetAResourceByAttributesEmpty(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_properties/1?properties[]=&properties[group][]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyProperty$"},
        "@id": {"pattern": "^/dummy_properties/1$"},
        "@type": {"pattern": "^DummyProperty$"},
        "group": {
          "type": "object",
          "properties": {
            "@id": {},
            "@type": {}
          },
          "additionalProperties": false,
          "required": ["@id", "@type"]
        }
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "group"]
    }
JSON);
    }

    public function testCreateAResourceByAttributesFooAndBar(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_properties?properties[]=foo&properties[]=bar', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyProperty",
      "@id": "/dummy_properties/11",
      "@type": "DummyProperty",
      "foo": "Foo",
      "bar": "Bar"
    }
JSON);
    }

    public function testCreateAResourceByAttributesFooBarGroupFooGroupBazAndGroupQux(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_properties?properties[]=foo&properties[]=bar&properties[group][]=foo&properties[group][]=baz&properties[group][]=qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "group": {
        "foo": "Foo",
        "baz": "Baz",
        "qux": "Qux"
      }
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyProperty",
      "@id": "/dummy_properties/12",
      "@type": "DummyProperty",
      "foo": "Foo",
      "bar": "Bar",
      "group": {
        "@id": "/dummy_groups/11",
        "@type": "DummyGroup",
        "foo": "Foo",
        "baz": null
      }
    }
JSON);
    }
}

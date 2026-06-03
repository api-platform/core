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
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;

final class GroupFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private static bool $fixturesLoaded = false;

    public static function getResources(): array
    {
        return [DummyGroup::class];
    }

    public static function tearDownAfterClass(): void
    {
        self::$fixturesLoaded = false;
        parent::tearDownAfterClass();
    }

    protected function loadFixtures(): void
    {
        if (self::$fixturesLoaded) {
            return;
        }
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM-only fixture; direct EntityManager persist of Entity\\DummyGroup is not portable to DocumentManager.');
        }
        self::createClient();
        $this->recreateSchema([DummyGroup::class]);

        /** @var EntityManagerInterface $manager */
        $manager = $this->getManager();

        for ($i = 1; $i <= 10; ++$i) {
            $group = new DummyGroup();
            foreach (['foo', 'bar', 'baz', 'qux'] as $field) {
                $group->{$field} = ucfirst($field).' #'.$i;
            }
            $manager->persist($group);
        }
        $manager->flush();
        $manager->clear();
        self::$fixturesLoaded = true;
    }

    public function testGetACollectionOfResourcesByGroupDummyFooWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?groups[]=dummy_foo', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?groups%5B%5D=dummy_foo&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByGroupDummyFooWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?override_groups[]=dummy_foo', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?override_groups%5B%5D=dummy_foo&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByGroupsDummyFooDummyQuxAndWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?groups[]=dummy_foo&groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz", "qux"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?groups%5B%5D=dummy_foo&groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByGroupsDummyFooDummyQuxAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?override_groups[]=dummy_foo&override_groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "qux"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {},
                "qux": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo", "qux"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?override_groups%5B%5D=dummy_foo&override_groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByGroupsDummyFooDummyQuxWithoutOverridingAndWithWhitelist(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?whitelisted_groups[]=dummy_foo&whitelisted_groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?whitelisted_groups%5B%5D=dummy_foo&whitelisted_groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByGroupsDummyFooDummyQuxWithOverridingAndWithWhitelist(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?override_whitelisted_groups[]=dummy_foo&override_whitelisted_groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "foo": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "foo"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?override_whitelisted_groups%5B%5D=dummy_foo&override_whitelisted_groups%5B%5D=dummy_qux&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByGroupEmptyAndWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?groups[]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {},
                "id": {},
                "foo": {},
                "bar": {},
                "baz": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type", "id", "foo", "bar", "baz"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?groups%5B%5D=&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetACollectionOfResourcesByGroupEmptyAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups?override_groups[]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type"]
            },
            {
              "type": "object",
              "properties": {
                "@id": {},
                "@type": {}
              },
              "additionalProperties": false,
              "required": ["@id", "@type"]
            }
          ],
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/dummy_groups\\?override_groups%5B%5D=&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetAResourceByGroupDummyFooWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?groups[]=dummy_foo', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz"]
    }
JSON);
    }

    public function testGetAResourceByGroupDummyFooWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?override_groups[]=dummy_foo', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "foo": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo"]
    }
JSON);
    }

    public function testGetAResourceByGroupsDummyFooDummyQuxAndWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?groups[]=dummy_foo&groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {},
        "qux": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz", "qux"]
    }
JSON);
    }

    public function testGetAResourceByGroupsDummyFooDummyQuxAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?override_groups[]=dummy_foo&override_groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "foo": {},
        "qux": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo", "qux"]
    }
JSON);
    }

    public function testGetAResourceByGroupsDummyFooDummyQuxAndWithoutOverridingAndWithWhitelist(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?whitelisted_groups[]=dummy_foo&whitelisted_groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz"]
    }
JSON);
    }

    public function testGetAResourceByGroupsDummyFooDummyQuxAndWithOverridingAndWithWhitelist(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?override_whitelisted_groups[]=dummy_foo&override_whitelisted_groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "foo": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "foo"]
    }
JSON);
    }

    public function testGetAResourceByGroupEmptyAndWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?groups[]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"},
        "id": {},
        "foo": {},
        "bar": {},
        "baz": {}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type", "id", "foo", "bar", "baz"]
    }
JSON);
    }

    public function testGetAResourceByGroupEmptyAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('GET', '/dummy_groups/1?override_groups[]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/DummyGroup$"},
        "@id": {"pattern": "^/dummy_groups/1$"},
        "@type": {"pattern": "^DummyGroup$"}
      },
      "additionalProperties": false,
      "required": ["@context", "@id", "@type"]
    }
JSON);
    }

    public function testCreateAResourceByGroupDummyFooAndWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?groups[]=dummy_foo', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/11",
      "@type": "DummyGroup",
      "id": 11,
      "foo": "Foo",
      "bar": "Bar",
      "baz": null
    }
JSON);
    }

    public function testCreateAResourceByGroupDummyFooAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?override_groups[]=dummy_foo', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/12",
      "@type": "DummyGroup",
      "foo": "Foo"
    }
JSON);
    }

    public function testCreateAResourceByGroupsDummyFooDummyBazDummyQuxAndWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?groups[]=dummy_foo&groups[]=dummy_baz&groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/13",
      "@type": "DummyGroup",
      "id": 13,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
JSON);
    }

    public function testCreateAResourceByGroupsDummyFooDummyBazDummyQuxAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?override_groups[]=dummy_foo&override_groups[]=dummy_baz&override_groups[]=dummy_qux', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/14",
      "@type": "DummyGroup",
      "foo": "Foo",
      "baz": "Baz",
      "qux": "Qux"
    }
JSON);
    }

    public function testCreateAResourceByGroupsDummyDummyBazWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?groups[]=dummy&groups[]=dummy_baz', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/15",
      "@type": "DummyGroup",
      "id": 15,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
JSON);
    }

    public function testCreateAResourceByGroupsDummyDummyBazAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?override_groups[]=dummy&override_groups[]=dummy_baz', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/16",
      "@type": "DummyGroup",
      "id": 16,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }
JSON);
    }

    public function testCreateAResourceByGroupEmptyAndWithoutOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?groups[]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/17",
      "@type": "DummyGroup",
      "id": 17,
      "foo": "Foo",
      "bar": "Bar",
      "baz": null
    }
JSON);
    }

    public function testCreateAResourceByGroupEmptyAndWithOverriding(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?override_groups[]=', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/18",
      "@type": "DummyGroup"
    }
JSON);
    }

    public function testCreateAResourceByGroupsDummyDummyBazWithoutOverridingAndWithWhitelist(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?whitelisted_groups[]=dummy&whitelisted_groups[]=dummy_baz', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/19",
      "@type": "DummyGroup",
      "id": 19,
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz"
    }
JSON);
    }

    public function testCreateAResourceByGroupsDummyDummyBazWithOverridingAndWithWhitelist(): void
    {
        $this->loadFixtures();

        $response = self::createClient()->request('POST', '/dummy_groups?override_whitelisted_groups[]=dummy&override_whitelisted_groups[]=dummy_baz', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'body' => '{
      "foo": "Foo",
      "bar": "Bar",
      "baz": "Baz",
      "qux": "Qux"
    }',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonEquals(<<<'JSON'
{
      "@context": "/contexts/DummyGroup",
      "@id": "/dummy_groups/20",
      "@type": "DummyGroup",
      "baz": "Baz"
    }
JSON);
    }
}

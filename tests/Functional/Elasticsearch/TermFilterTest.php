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

namespace ApiPlatform\Tests\Functional\Elasticsearch;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\Elasticsearch\Model\Book;
use ApiPlatform\Tests\Fixtures\Elasticsearch\Model\Genre;
use ApiPlatform\Tests\Fixtures\Elasticsearch\Model\Library;
use ApiPlatform\Tests\Fixtures\Elasticsearch\Model\Tweet;
use ApiPlatform\Tests\Fixtures\Elasticsearch\Model\User;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class TermFilterTest extends ApiTestCase
{
    use ElasticsearchSetupTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [User::class, Tweet::class, Library::class, Book::class, Genre::class];
    }

    public function testTermFilterOnAnIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/users?id=%2Fusers%2Fcf875c95-41ab-48df-af66-38c74db18f72', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/cf875c95-41ab-48df-af66-38c74db18f72$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?id=%2Fusers%2Fcf875c95-41ab-48df-af66-38c74db18f72$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnAPropertyOfKeywordType(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/users?gender=female', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 3,
          "minItems": 3,
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/users/6a457188-d1ba-45e3-8509-81e5c66a5297$"},
                  {"pattern": "^/users/89d4ae3d-73bc-4382-b01c-adf038f893c2$"},
                  {"pattern": "^/users/cf875c95-41ab-48df-af66-38c74db18f72$"}
                ]
              },
              "gender": {"pattern": "^female$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?gender=female&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testCombiningTermFiltersOnAPropertyOfIntegerTypeAndAPropertyOfKeywordType(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/users?age=42&gender=female', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 2,
          "minItems": 2,
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/users/89d4ae3d-73bc-4382-b01c-adf038f893c2$"},
                  {"pattern": "^/users/fa7d4578-6692-47ec-9346-a8ab25ca613c$"}
                ]
              },
              "age": {
                "type": "integer",
                "maximum": 42,
                "minimum": 42
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?age=42&gender=female$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testCombiningTermFiltersOnAPropertyOfIntegerTypeAndAPropertyOfKeywordTypeReturningNoMatch(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/users?age=42&gender=male', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0,
          "minItems": 0
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?age=42&gender=male$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnAPropertyOfTextType(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/users?firstName=xavier', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/f18eb7ab-6985-4e05-afd4-13a638c929d4$"},
              "firstName": {"pattern": "^Xavier$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?firstName=xavier$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnANestedIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/users?tweets.id=%2Ftweets%2Fdcaef1db-225d-442b-960e-5de6984a44be', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/89d4ae3d-73bc-4382-b01c-adf038f893c2$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?tweets.id=%2Ftweets%2Fdcaef1db-225d-442b-960e-5de6984a44be$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnANestedPropertyOfDateType(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/users?tweets.date=2018-02-02%2014%3A14%3A14', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/User$"},
        "@id": {"pattern": "^/users$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/users/fa7d4578-6692-47ec-9346-a8ab25ca613c$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/users\\?tweets.date=2018-02-02%2014%3A14%3A14$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnAnIdentifierPropertyWithElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/libraries?id=%2Flibraries%2Fcf875c95-41ab-48df-af66-38c74db18f72', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Library$"},
        "@id": {"pattern": "^/libraries$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/libraries/cf875c95-41ab-48df-af66-38c74db18f72$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/libraries\\?id=%2Flibraries%2Fcf875c95-41ab-48df-af66-38c74db18f72$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnAPropertyOfKeywordTypeWithElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/libraries?gender=female', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Library$"},
        "@id": {"pattern": "^/libraries$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 3,
          "minItems": 3,
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/libraries/6a457188-d1ba-45e3-8509-81e5c66a5297$"},
                  {"pattern": "^/libraries/89d4ae3d-73bc-4382-b01c-adf038f893c2$"},
                  {"pattern": "^/libraries/cf875c95-41ab-48df-af66-38c74db18f72$"}
                ]
              },
              "gender": {"pattern": "^female$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/libraries\\?gender=female&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testCombiningTermFiltersOnAPropertyOfIntegerTypeAndAPropertyOfKeywordTypeWithElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/libraries?age=42&gender=female', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Library$"},
        "@id": {"pattern": "^/libraries$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 2,
          "minItems": 2,
          "items": {
            "type": "object",
            "properties": {
              "@id": {
                "oneOf": [
                  {"pattern": "^/libraries/89d4ae3d-73bc-4382-b01c-adf038f893c2$"},
                  {"pattern": "^/libraries/fa7d4578-6692-47ec-9346-a8ab25ca613c$"}
                ]
              },
              "age": {
                "type": "integer",
                "maximum": 42,
                "minimum": 42
              }
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/libraries\\?age=42&gender=female$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testCombiningTermFiltersOnAPropertyOfIntegerTypeAndAPropertyOfKeywordTypeReturningNoMatchWithElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/libraries?age=42&gender=male', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Library$"},
        "@id": {"pattern": "^/libraries$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 0,
          "minItems": 0
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/libraries\\?age=42&gender=male$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnAPropertyOfTextTypeWithElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/libraries?firstName=xavier', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Library$"},
        "@id": {"pattern": "^/libraries$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/libraries/f18eb7ab-6985-4e05-afd4-13a638c929d4$"},
              "firstName": {"pattern": "^Xavier$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/libraries\\?firstName=xavier$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnANestedIdentifierPropertyWithElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/libraries?books.id=%2Fbooks%2Fdcaef1db-225d-442b-960e-5de6984a44be', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Library$"},
        "@id": {"pattern": "^/libraries$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/libraries/89d4ae3d-73bc-4382-b01c-adf038f893c2$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/libraries\\?books.id=%2Fbooks%2Fdcaef1db-225d-442b-960e-5de6984a44be$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testTermFilterOnANestedPropertyOfDateTypeWithElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/libraries?books.date=2018-02-02%2014%3A14%3A14', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Library$"},
        "@id": {"pattern": "^/libraries$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "maxItems": 1,
          "minItems": 1,
          "items": {
            "type": "object",
            "properties": {
              "@id": {"pattern": "^/libraries/fa7d4578-6692-47ec-9346-a8ab25ca613c$"}
            }
          }
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/libraries\\?books.date=2018-02-02%2014%3A14%3A14$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }
}

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

final class MatchFilterTest extends ApiTestCase
{
    use ElasticsearchSetupTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [User::class, Tweet::class, Library::class, Book::class, Genre::class];
    }

    public function testMatchFilterOnATextProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?message=Good%20job', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?message=Good%20job$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testMatchFilterOnATextPropertyWithMultipleValues(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?message%5B%5D=Good%20job&message%5B%5D=run', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/9de3308c-6f82-4a57-a33c-4e3cd5d5a3f6$"
                }
              }
            }
          ]
        },
        "hydra:totalItem": {
          "type": "string",
          "pattern": "^4$"
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?message%5B%5D=Good%20job&message%5B%5D=run&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testMatchFilterOnANestedPropertyOfTextType(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?author.firstName=Caroline', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?author.firstName=Caroline$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testCombiningMatchFiltersOnPropertiesOfTextTypeAndANestedPropertyOfTextType(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?message%5B%5D=Good%20job&message%5B%5D=run&author.firstName=Caroline', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Tweet$"},
        "@id": {"pattern": "^/tweets$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?author.firstName=Caroline&message%5B%5D=Good%20job&message%5B%5D=run$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testMatchFilterOnATextPropertyWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?message=Good%20job', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Book$"},
        "@id": {"pattern": "^/books$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?message=Good%20job$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testMatchFilterOnATextPropertyWithMultipleValuesWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?message%5B%5D=Good%20job&message%5B%5D=run', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Book$"},
        "@id": {"pattern": "^/books$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 3,
          "minItems": 3,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/7cdadcda-3fb5-4312-9e32-72acba323cc0$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/9de3308c-6f82-4a57-a33c-4e3cd5d5a3f6$"
                }
              }
            }
          ]
        },
        "hydra:totalItem": {
          "type": "string",
          "pattern": "^4$"
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?message%5B%5D=Good%20job&message%5B%5D=run&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testMatchFilterOnANestedPropertyOfTextTypeWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?library.firstName=Caroline', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Book$"},
        "@id": {"pattern": "^/books$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?library.firstName=Caroline$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testCombiningMatchFiltersOnPropertiesOfTextTypeAndANestedPropertyOfTextTypeWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?message%5B%5D=Good%20job&message%5B%5D=run&library.firstName=Caroline', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Book$"},
        "@id": {"pattern": "^/books$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/6d82a76c-8ba2-4e78-9ab3-6a456e4470c3$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?library.firstName=Caroline&message%5B%5D=Good%20job&message%5B%5D=run$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testMatchFilterOnAMultiLevelNestedPropertyOfTextTypeWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?library.relatedGenres.name=Fiction', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesJsonSchema(<<<'JSON'
{
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/Book$"},
        "@id": {"pattern": "^/books$"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "additionalItems": false,
          "maxItems": 2,
          "minItems": 2,
          "items": [
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/0acfd90d-5bfe-4e42-b708-dc38bf20677c$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?library.relatedGenres.name=Fiction$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }
}

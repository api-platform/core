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

final class OrderFilterTest extends ApiTestCase
{
    use ElasticsearchSetupTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [User::class, Tweet::class, Library::class, Book::class, Genre::class];
    }

    public function testGetCollectionOrderedInAscendingOrderOnAnIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?order%5Bid%5D=asc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/tweets/0acfd90d-5bfe-4e42-b708-dc38bf20677c$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/0cfe3d33-6116-416b-8c50-3b8319331998$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/1c9e0545-1b37-4a9a-83e0-30400d0b354e$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInDescendingOrderOnAnIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?order%5Bid%5D=desc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/tweets/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f2e65123-e063-44a0-b640-b0a04554d19e$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInAscendingOrderOnAnIdentifierPropertyAndInAscendingOrderOnANestedIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?order%5Bauthor.id%5D=asc&order%5Bid%5D=asc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/tweets/89601e1c-3ef2-4ef7-bca2-7511d38611c6$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/9da70727-d656-42d9-876a-1be6321f171b$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=asc&order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInDescendingOrderOnAnIdentifierPropertyAndInAscendingOrderOnANestedIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?order%5Bauthor.id%5D=asc&order%5Bid%5D=desc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/tweets/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/9da70727-d656-42d9-876a-1be6321f171b$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/89601e1c-3ef2-4ef7-bca2-7511d38611c6$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=asc&order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInAscendingOrderOnAnIdentifierPropertyAndInDescendingOrderOnANestedIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?order%5Bauthor.id%5D=desc&order%5Bid%5D=asc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/tweets/3a1d02fa-2347-41ff-80ef-ed9b9c0efea9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/1c9e0545-1b37-4a9a-83e0-30400d0b354e$"
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
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=desc&order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInDescendingOrderOnAnIdentifierPropertyAndInDescendingOrderOnANestedIdentifierProperty(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/tweets?order%5Bauthor.id%5D=desc&order%5Bid%5D=desc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/tweets/3a1d02fa-2347-41ff-80ef-ed9b9c0efea9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/tweets/811e4d1c-df3f-4d24-a9da-2a28080c85f5$"
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
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/tweets\\?order%5Bauthor.id%5D=desc&order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInAscendingOrderOnAnIdentifierPropertyWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?order%5Bid%5D=asc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/books/0acfd90d-5bfe-4e42-b708-dc38bf20677c$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/0cfe3d33-6116-416b-8c50-3b8319331998$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/1c9e0545-1b37-4a9a-83e0-30400d0b354e$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInDescendingOrderOnAnIdentifierPropertyWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?order%5Bid%5D=desc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/books/f91bca21-b5f8-405b-9b08-d5a5dc476a92$"
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
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/f2e65123-e063-44a0-b640-b0a04554d19e$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInAscendingOrderOnAnIdentifierPropertyAndInAscendingOrderOnANestedIdentifierPropertyWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?order%5Blibrary.id%5D=asc&order%5Bid%5D=asc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/books/89601e1c-3ef2-4ef7-bca2-7511d38611c6$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/9da70727-d656-42d9-876a-1be6321f171b$"
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
            "@id": {"pattern": "^/books\\?order%5Blibrary.id%5D=asc&order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInDescendingOrderOnAnIdentifierPropertyAndInAscendingOrderOnANestedIdentifierPropertyWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?order%5Blibrary.id%5D=asc&order%5Bid%5D=desc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/books/f36a0026-0635-4865-86a6-5adb21d94d64$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/9da70727-d656-42d9-876a-1be6321f171b$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/89601e1c-3ef2-4ef7-bca2-7511d38611c6$"
                }
              }
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?order%5Blibrary.id%5D=asc&order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInAscendingOrderOnAnIdentifierPropertyAndInDescendingOrderOnANestedIdentifierPropertyWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?order%5Blibrary.id%5D=desc&order%5Bid%5D=asc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/books/3a1d02fa-2347-41ff-80ef-ed9b9c0efea9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/1c9e0545-1b37-4a9a-83e0-30400d0b354e$"
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
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?order%5Blibrary.id%5D=desc&order%5Bid%5D=asc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }

    public function testGetCollectionOrderedInDescendingOrderOnAnIdentifierPropertyAndInDescendingOrderOnANestedIdentifierPropertyWithNewElasticsearchOperations(): void
    {
        $this->skipIfNotElasticsearch();
        $this->initializeElasticsearch();

        $response = self::createClient()->request('GET', '/books?order%5Blibrary.id%5D=desc&order%5Bid%5D=desc', ['headers' => ['Accept' => 'application/ld+json']]);

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
                  "pattern": "^/books/3a1d02fa-2347-41ff-80ef-ed9b9c0efea9$"
                }
              }
            },
            {
              "type": "object",
              "properties": {
                "@id": {
                  "type": "string",
                  "pattern": "^/books/811e4d1c-df3f-4d24-a9da-2a28080c85f5$"
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
            }
          ]
        },
        "hydra:view": {
          "type": "object",
          "properties": {
            "@id": {"pattern": "^/books\\?order%5Blibrary.id%5D=desc&order%5Bid%5D=desc&page=1$"},
            "@type": {"pattern": "^hydra:PartialCollectionView$"}
          }
        }
      }
    }
JSON);
    }
}

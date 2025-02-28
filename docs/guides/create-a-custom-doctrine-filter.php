<?php
// ---
// slug: create-a-custom-doctrine-filter
// name: Create a Custom Doctrine Filter
// executable: true
// position: 10
// tags: doctrine, expert
// ---

// Custom filters can be written by implementing the `ApiPlatform\Metadata\FilterInterface` interface.
//
// API Platform provides a convenient way to create Doctrine ORM and MongoDB ODM filters. If you use [custom state providers](/docs/guide/state-providers), you can still create filters by implementing the previously mentioned interface, but - as API Platform isn't aware of your persistence system's internals - you have to create the filtering logic by yourself.
//
// Doctrine ORM filters have access to the context created from the HTTP request and to the `QueryBuilder` instance used to retrieve data from the database. They are only applied to collections. If you want to deal with the DQL query generated to retrieve items, [extensions](/docs/core/extensions/) are the way to go.
//
// A Doctrine ORM filter is basically a class implementing the `ApiPlatform\Doctrine\Orm\Filter\FilterInterface`. API Platform includes a convenient abstract class implementing this interface and providing utility methods: `ApiPlatform\Doctrine\Orm\Filter\AbstractFilter`.
//
// Note: Doctrine MongoDB ODM filters have access to the context created from the HTTP request and to the [aggregation builder](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/aggregation-builder.html) instance used to retrieve data from the database and to execute [complex operations on data](https://docs.mongodb.com/manual/aggregation/). They are only applied to collections. If you want to deal with the aggregation pipeline generated to retrieve items, [extensions](/docs/core/extensions/) are the way to go.
//
// A Doctrine MongoDB ODM filter is basically a class implementing the `ApiPlatform\Doctrine\Odm\Filter\FilterInterface`. API Platform includes a convenient abstract class implementing this interface and providing utility methods: `ApiPlatform\Doctrine\Odm\Filter\AbstractFilter`.
//
// In this example, we create a class to filter a collection by applying a regular expression to a property. The `REGEXP` DQL function used in this example can be found in the [DoctrineExtensions](https://github.com/beberlei/DoctrineExtensions) library. This library must be properly installed and registered to use this example (works only with MySQL).

namespace App\Filter {
    use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
    use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
    use ApiPlatform\Metadata\Operation;
    use Doctrine\ORM\QueryBuilder;

    final class RegexpFilter extends AbstractFilter
    {
        /*
         * Filtered properties is accessible through getProperties() method: property => strategy
         */
        protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
        {
            /*
             * Otherwise this filter is applied to order and page as well.
             */
            if (
                !$this->isPropertyEnabled($property, $resourceClass)
                || !$this->isPropertyMapped($property, $resourceClass)
            ) {
                return;
            }

            /*
             * Generate a unique parameter name to avoid collisions with other filters.
             */
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $queryBuilder
                ->andWhere(sprintf('REGEXP(o.%s, :%s) = 1', $property, $parameterName))
                ->setParameter($parameterName, $value);
        }

        /*
         * This function is only used to hook in documentation generators (supported by Swagger and Hydra).
         */
        public function getDescription(string $resourceClass): array
        {
            if (!$this->properties) {
                return [];
            }

            $description = [];
            foreach ($this->properties as $property => $strategy) {
                $description["regexp_$property"] = [
                    'property' => $property,
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Filter using a regex. This will appear in the OpenAPI documentation!',
                    'openapi' => [
                        'example' => 'Custom example that will be in the documentation and be the default value of the sandbox',
                        /*
                         * If true, query parameters will be not percent-encoded
                         */
                        'allowReserved' => false,
                        'allowEmptyValue' => true,
                        /*
                         * To be true, the type must be Type::BUILTIN_TYPE_ARRAY, ?product=blue,green will be ?product[]=blue&product[]=green
                         */
                        'explode' => false,
                    ],
                ];
            }

            return $description;
        }
    }
}

namespace App\Entity {
    use ApiPlatform\Metadata\ApiFilter;
    use ApiPlatform\Metadata\ApiResource;
    use App\Filter\RegexpFilter;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity]
    #[ApiResource]
    #[ApiFilter(RegexpFilter::class, properties: ['title'])]
    class Book
    {
        #[ORM\Column(type: 'integer')]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        private $id;

        #[ORM\Column]
        public string $title;

        #[ORM\Column]
        #[ApiFilter(RegexpFilter::class)]
        public string $author;
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books.jsonld?regexp_title=^[Found]', 'GET');
    }
}

namespace DoctrineMigrations {
    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;

    final class Migration extends AbstractMigration
    {
        public function up(Schema $schema): void
        {
            $this->addSql('CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL)');
        }
    }
}

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
    use App\Entity\Book;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testAsAnonymousICanAccessTheDocumentation(): void
        {
            static::createClient()->request('GET', '/books.jsonld?regexp_title=^[Found]');

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Book::class, '_api_/books{._format}_get_collection');
            $this->assertJsonContains([
                'search' => [
                    '@type' => 'IriTemplate',
                    'template' => '/books.jsonld{?regexp_title,regexp_author}',
                    'variableRepresentation' => 'BasicRepresentation',
                    'mapping' => [
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'regexp_title',
                            'property' => 'title',
                            'required' => false,
                        ],
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'regexp_author',
                            'property' => 'author',
                            'required' => false,
                        ],
                    ],
                ],
            ]);
        }
    }
}

<?php
// ---
// slug: create-a-custom-doctrine-filter
// name: Create a Custom Doctrine Filter
// executable: true
// position: 10
// tags: doctrine, expert
// ---

// Custom filters allow you to execute specific logic directly on the Doctrine QueryBuilder.
//
// While API Platform provides many built-in filters (Search, Date, Range...), you often need to implement custom business logic. The recommended way is to implement the `ApiPlatform\Metadata\FilterInterface` and link it to a `QueryParameter`.
//
// A Doctrine ORM filter has access to the `QueryBuilder` and the `QueryParameter` context.
//
// In this example, we create a `MinLengthFilter` that filters resources where the length of a property is greater than or equal to a specific value. We map this filter to specific API parameters using the `#[QueryParameter]` attribute on our resource.

namespace App\Filter {
    use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
    use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
    use ApiPlatform\Metadata\Operation;
    use Doctrine\ORM\QueryBuilder;

    final class MinLengthFilter implements FilterInterface
    {
        //The `apply` method is where the filtering logic happens.
        //We retrieve the parameter definition and its value from the context.
        public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
        {
            $parameter = $context['parameter'] ?? null;
            $value = $parameter?->getValue();

            //If the value is missing or invalid, we skip the filter.
            if (!$value) {
                return;
            }

            // We determine which property to filter on.
            // The `QueryParameter` attribute provides the property name (explicitly or inferred).
            $property = $parameter->getProperty();
            if (!$property) {
                return;
            }

            // Generate a unique parameter name to avoid collisions in the DQL.
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $alias = $queryBuilder->getRootAliases()[0];

            $queryBuilder
                ->andWhere(sprintf('LENGTH(%s.%s) >= :%s', $alias, $property, $parameterName))
                ->setParameter($parameterName, $value);
        }

        // Note: The `getDescription` method is no longer needed when using `QueryParameter`
        // because the documentation is handled by the attribute itself.
        public function getDescription(string $resourceClass): array
        {
            return [];
        }
    }
}

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\QueryParameter;
    use App\Filter\MinLengthFilter;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity]
    #[ApiResource(
        operations: [
            new GetCollection(
                parameters: [
                    // We define a parameter 'min_length' that filters on the `title` and the `author` property using our custom logic.
                    'min_length[:property]' => new QueryParameter(
                        filter: MinLengthFilter::class,
                        properties: ['title', 'author'],
                    ),
                ]
            )
        ]
    )]
    class Book
    {
        #[ORM\Column(type: 'integer')]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'AUTO')]
        private $id;

        #[ORM\Column]
        public string $title;

        #[ORM\Column]
        public string $author;
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books.jsonld?min_length[title]=10', 'GET');
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
            static::createClient()->request('GET', '/books.jsonld?min_length[title]=10');

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Book::class, '_api_/books{._format}_get_collection');
            $this->assertJsonContains([
                'search' => [
                    '@type' => 'IriTemplate',
                    'template' => '/books.jsonld{?min_length[title],min_length[author]}',
                    'variableRepresentation' => 'BasicRepresentation',
                    'mapping' => [
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'min_length[title]',
                            'property' => 'title',
                            'required' => false,
                        ],
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'min_length[author]',
                            'property' => 'author',
                            'required' => false,
                        ],
                    ],
                ],
            ]);
        }
    }
}

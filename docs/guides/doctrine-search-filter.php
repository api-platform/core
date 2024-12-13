<?php
// ---
// position: 7
// slug: doctrine-search-filter
// name: Doctrine ORM SearchFilter
// executable: true
// tags: doctrine
// ---

// API Platform provides a generic system to apply filters and sort criteria on collections. Useful filters for Doctrine ORM, MongoDB ODM and ElasticSearch are provided with the library.
//
// By default, all filters are disabled. They must be enabled explicitly.

namespace App\Entity {
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\QueryParameter;
    use Doctrine\ORM\Mapping as ORM;

    #[GetCollection(
        uriTemplate: 'books{._format}',
        parameters: [
            // Declare a QueryParameter with the :property pattern that matches the properties declared on the Filter.
            // The filter is a service declared in the next class.
            ':property' => new QueryParameter(filter: 'app.search_filter'),
        ]
    )]
    #[ORM\Entity]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id = null;

        #[ORM\Column]
        public ?string $title = null;

        #[ORM\Column]
        public ?string $author = null;
    }
}

namespace App\DependencyInjection {
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    function configure(ContainerConfigurator $configurator): void
    {
        // This is the custom search filter we declare, if you prefer to use decoration, suffix the parent service with `.instance`. They implement the `PropertyAwareFilterInterface` that allows you to override a filter's property.
        $services = $configurator->services();
        $services->set('app.search_filter')
                 ->parent('api_platform.doctrine.orm.search_filter')
                // Search strategies may be defined here per properties, [read more](https://api-platform.com/docs/core/filters/) on the filter documentation.
                 ->args([['author' => 'partial', 'title' => 'partial']])
                 ->tag('api_platform.filter');
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        // Try changing the search value [in the interactive Playground](/playground/doctrine-search-filter).
        return Request::create('/books.jsonld?author=a', 'GET');
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

namespace App\Fixtures {
    use App\Entity\Book;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;

    use function Zenstruck\Foundry\anonymous;
    use function Zenstruck\Foundry\faker;
    use function Zenstruck\Foundry\repository;

    final class BookFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            $bookFactory = anonymous(Book::class);
            if (repository(Book::class)->count()) {
                return;
            }

            $bookFactory->many(10)->create(fn () => [
                'title' => faker()->name(),
                'author' => faker()->firstName(),
            ]);
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

        public function testGetDocumentation(): void
        {
            static::createClient()->request('GET', '/books.jsonld');

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Book::class, '_api_books{._format}_get_collection', 'jsonld');
            $this->assertJsonContains([
                'search' => [
                    '@type' => 'IriTemplate',
                    'template' => '/books.jsonld{?title,author}',
                    'variableRepresentation' => 'BasicRepresentation',
                    'mapping' => [
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'title',
                            'property' => 'title',
                            'required' => false,
                        ],
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'author',
                            'property' => 'author',
                            'required' => false,
                        ],
                    ],
                ],
            ]);
        }
    }
}

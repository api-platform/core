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
    use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
    use ApiPlatform\Metadata\ApiFilter;
    use ApiPlatform\Metadata\ApiResource;
    use Doctrine\ORM\Mapping as ORM;

    #[ApiResource]
    //
    // By using the `#[ApiFilter]` attribute, this attribute automatically declares the service,
    // and you just have to use the filter class you want.
    //
    // If the filter is declared on the resource, you can specify on which properties it applies.
    #[ApiFilter(SearchFilter::class, properties: ['title'])]
    #[ORM\Entity]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id = null;

        #[ORM\Column]
        public ?string $title = null;

        #[ORM\Column]
        // We can also declare the filter attribute on a property and specify the strategy that should be used.
        // For a list of availabe options [head to the documentation](/docs/core/filters/#search-filter)
        #[ApiFilter(SearchFilter::class, strategy: 'partial')]
        public ?string $author = null;
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
            ]
            );
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
            static::createClient()->request('GET', '/books.jsonld');

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Book::class, '_api_/books{._format}_get_collection', 'jsonld');
            $this->assertJsonContains([
                'hydra:search' => [
                    '@type' => 'hydra:IriTemplate',
                    'hydra:template' => '/books.jsonld{?title,title[],author}',
                    'hydra:variableRepresentation' => 'BasicRepresentation',
                    'hydra:mapping' => [
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'title',
                            'property' => 'title',
                            'required' => false,
                        ],
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'title[]',
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

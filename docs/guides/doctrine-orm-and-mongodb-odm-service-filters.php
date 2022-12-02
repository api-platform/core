<?php
// ---
// slug: doctrine-orm-and-mongodb-odm-service-filters
// name: Doctrine ORM and MongoDB ODM Service Filters
// executable: true
// ---

// API Platform provides a generic system to apply filters and sort criteria on collections. Useful filters for Doctrine ORM, MongoDB ODM and ElasticSearch are provided with the library.
//
// By default, all filters are disabled. They must be enabled explicitly.
//
// Filters can be declared as services (see [custom filters](/docs/guide/custom-filters)), and they can be linked to a Resource as following:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use Doctrine\ORM\Mapping as ORM;

    /*
     * Links the filter declared as a service `book.search_filter` with the resource.
     */
    #[ApiResource(filters: ['book.search_filter'])]
    #[ORM\Entity]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id = null;

        #[ORM\Column]
        public ?string $title = null;
    }
}

namespace App\DependencyInjection {
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    function configure(ContainerConfigurator $configurator) {
        $configurator->services()
            ->set('book.search_filter')
            ->parent('api_platform.doctrine.orm.search_filter')
            ->args([['title' => null]])
            ->tag('api_platform.filter')
            ->autowire(false)
            ->autoconfigure(false)
            ->public(false)
        ;
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books.jsonld', 'GET');
    }
}

namespace DoctrineMigrations {
    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;

    final class Migration extends AbstractMigration
    {
        public function up(Schema $schema): void
        {
            $this->addSql('CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL)');
        }

        public function down(Schema $schema): void
        {
            $this->addSql('DROP TABLE book');
        }
    }
}

namespace App\Tests {
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
    use App\Entity\Book;
    use ApiPlatform\Playground\Test\TestGuideTrait;

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
                    'hydra:template' => '/books.jsonld{?title,title[]}',
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
                    ],
                ],
            ]);
        }
    }
}

<?php
// ---
// slug: doctrine-orm-and-odm-attribute-filters
// name: Doctrine ORM and ODM Attribute Filters
// executable: true
// ---

// API Platform provides a generic system to apply filters and sort criteria on collections. Useful filters for Doctrine ORM, MongoDB ODM and ElasticSearch are provided with the library.
//
// By default, all filters are disabled. They must be enabled explicitly.
//
// Filters can be declared as attributes (see [custom filters](/docs/guide/custom-filters)), and they can be linked to a Resource or a property as following:

namespace App\Entity {
    use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
    use ApiPlatform\Metadata\ApiFilter;
    use ApiPlatform\Metadata\ApiResource;
    use Doctrine\ORM\Mapping as ORM;

    #[ApiResource]
    /*
     * By using the `#[ApiFilter]` attribute, this attribute automatically declares the service,
     * and you just have to use the filter class you want.
     *
     * If the filter is declared on the resource, you can specify on which properties it applies.
     */
    #[ApiFilter(SearchFilter::class, properties: ['title'])]
    #[ORM\Entity]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id = null;

        #[ORM\Column]
        public ?string $title = null;

        #[ORM\Column]
        /*
         * When declaring a filter on a property, no need to specify the `properties` option.
         */
        #[ApiFilter(SearchFilter::class)]
        public ?string $author = null;
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
            $this->addSql('CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL)');
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
                    'hydra:template' => '/books.jsonld{?title,title[],author,author[]}',
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
                        [
                            '@type' => 'IriTemplateMapping',
                            'variable' => 'author[]',
                            'property' => 'author',
                            'required' => false,
                        ],
                    ],
                ],
            ]);
        }
    }
}

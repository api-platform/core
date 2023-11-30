<?php
// ---
// slug: doctrine-entity-as-resource
// name: Doctrine entity as API resource
// position: 4
// tags: doctrine
// executable: true
// ---
//
// API Platform is compatible with [Doctrine ORM](https://www.doctrine-project.org), all we need is to declare an

namespace App\Entity {
    use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
    use ApiPlatform\Metadata\ApiFilter;
    use ApiPlatform\Metadata\ApiResource;
    use Doctrine\ORM\Mapping as ORM;

    // When an ApiResource is declared on an `\ORM\Entity` we have access to [Doctrine filters](/docs/core/filters/).
    #[ApiResource]
    #[ApiFilter(OrderFilter::class)]
    #[ORM\Entity]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\Column]
        public ?string $title = null;

        public function getId(): ?int
        {
            return $this->id;
        }
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        // Persistence is automatic, you can try to create or read data:
        return Request::create('/books?order[id]=desc', 'GET');

        return Request::create('/books/1', 'GET');

        return Request::create(uri: '/books', method: 'POST', server: ['CONTENT_TYPE' => 'application/ld+json'], content: json_encode(['id' => 1, 'title' => 'API Platform rocks.']));
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
            ]
            );
        }
    }
}

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testGet(): void
        {
            static::createClient()->request('GET', '/books.jsonld');
            $this->assertResponseIsSuccessful();
        }

        public function testGetOne(): void
        {
            static::createClient()->request('GET', '/books/1.jsonld');
            $this->assertResponseIsSuccessful();
        }
    }
}

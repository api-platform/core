<?php
// ---
// slug: how-to
// name: How to create a Guide
// executable: false
// position: 0
// ---

// This guide describes every available options to create a guide. Everything is optional and we recommend not to use Doctrine unless the guide
// is doctrine-specific. We provide a SQLite database, a way to run migrations and fixtures if needed.
//
// Usually a guide has a Front Matter header:
//
// ```
// slug: how-to
// name: How to create a Guide
// // When this is true we'll be able to run the guide in api-platform.com/playground
// executable: false
// position: 0
// tags: [doctrine]
// ```
//
// Two namespaces are available to register API resources: `App\Entity` (for Doctrine) and `App\ApiResource`.

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use Doctrine\ORM\Mapping as ORM;

    // To showcase all the guide possibilities we're using a Doctrine Entity `Book`:
    #[ApiResource]
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

// We can declare as many namespaces or classes that we need to for this code to work.

namespace App\Service {
    use Psr\Log\LoggerInterface;

    class MyService
    {
        public function __construct(private LoggerInterface $logger)
        {
        }
    }
}

// If you need to change something within Symfony's Container you need to declare this namespace with a `configure` method.

namespace App\DependencyInjection {
    use App\Service\MyService;
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

    function configure(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();
        $services->set(MyService::class)
            ->args([service('logger')]);
    }
}

// Doctrine migrations will run from this namespace.

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

// And we can load fixtures using [Foundry](https://github.com/zenstruck/foundry)

namespace App\Fixtures {
    use App\Entity\Book;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;

    use function Zenstruck\Foundry\anonymous;

    final class BookFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            $bookFactory = anonymous(Book::class);
            $bookFactory->many(10)->create([
                'title' => 'title',
            ]);
        }
    }
}

// The `request` method is the one executed by the API Platform online Playground on startup.

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books.jsonld', 'GET');
    }
}

// The Guide huge advantage is that it is also tested with phpunit.

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
    use App\Entity\Book;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testAsAnonymousICanAccessTheDocumentation(): void
        {
            $response = static::createClient()->request('GET', '/books.jsonld');

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Book::class, '_api_/books{._format}_get_collection', 'jsonld');
            $this->assertNotSame(0, $response->toArray(false)['totalItems'], 'The collection is empty.');
            $this->assertJsonContains([
                'totalItems' => 10,
            ]);
        }
    }
}

<?php
// ---
// slug: custom-pagination
// name: Custom pagination
// executable: true
// position: 12
// tags: expert
// ---

// In case you're using a custom collection (through a Provider), make sure you return the `Paginator` object to get the full hydra response with `view` (which contains information about first, last, next and previous page).
//
// The following example shows how to handle it using a custom Provider. You will need to use the Doctrine Paginator and pass it to the API Platform Paginator.

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\GetCollection;
    use App\Repository\BookRepository;
    use App\State\BooksListProvider;
    use Doctrine\ORM\Mapping as ORM;

    /* Use custom Provider on operation to retrieve the custom collection */
    #[ApiResource(
        operations: [
            new GetCollection(provider: BooksListProvider::class),
        ]
    )]
    #[ORM\Entity(repositoryClass: BookRepository::class)]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id = null;

        #[ORM\Column]
        public ?string $title = null;

        #[ORM\Column(name: 'is_published', type: 'boolean')]
        public ?bool $published = null;
    }
}

namespace App\Repository {
    use App\Entity\Book;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Common\Collections\Criteria;
    use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
    use Doctrine\Persistence\ManagerRegistry;

    class BookRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, Book::class);
        }

        public function getPublishedBooks(int $page = 1, int $itemsPerPage = 30): DoctrinePaginator
        {
            /* Retrieve the custom collection and inject it into a Doctrine Paginator object */
            return new DoctrinePaginator(
                $this->createQueryBuilder('b')
                     ->where('b.published = :isPublished')
                     ->setParameter('isPublished', true)
                     ->addCriteria(
                         Criteria::create()
                             ->setFirstResult(($page - 1) * $itemsPerPage)
                             ->setMaxResults($itemsPerPage)
                     )
            );
        }
    }
}

namespace App\State {
    use ApiPlatform\Doctrine\Orm\Paginator;
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\Pagination\Pagination;
    use ApiPlatform\State\ProviderInterface;
    use App\Repository\BookRepository;

    class BooksListProvider implements ProviderInterface
    {
        public function __construct(private readonly BookRepository $bookRepository, private readonly Pagination $pagination)
        {
        }

        public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator
        {
            /* Retrieve the pagination parameters from the context thanks to the Pagination object */
            [$page, , $limit] = $this->pagination->getPagination($operation, $context);

            /* Decorates the Doctrine Paginator object to the API Platform Paginator one */
            return new Paginator($this->bookRepository->getPublishedBooks($page, $limit));
        }
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
            $this->addSql('CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, is_published SMALLINT NOT NULL)');
        }
    }
}

namespace App\Fixtures {
    use App\Entity\Book;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;
    use Zenstruck\Foundry\AnonymousFactory;

    use function Zenstruck\Foundry\faker;

    final class BookFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            /* Create books published or not */
            $factory = AnonymousFactory::new(Book::class);
            $factory->many(5)->create(static function (int $i): array {
                return [
                    'title' => faker()->title(),
                    'published' => false,
                ];
            });
            $factory->many(35)->create(static function (int $i): array {
                return [
                    'title' => faker()->title(),
                    'published' => true,
                ];
            });
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

        public function testTheCustomCollectionIsPaginated(): void
        {
            $response = static::createClient()->request('GET', '/books.jsonld');

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Book::class, '_api_/books{._format}_get_collection', 'jsonld');
            $this->assertNotSame(0, $response->toArray(false)['totalItems'], 'The collection is empty.');
            $this->assertJsonContains([
                'totalItems' => 35,
                'view' => [
                    '@id' => '/books.jsonld?page=1',
                    '@type' => 'PartialCollectionView',
                    'first' => '/books.jsonld?page=1',
                    'last' => '/books.jsonld?page=2',
                    'next' => '/books.jsonld?page=2',
                ],
            ]);
        }
    }
}

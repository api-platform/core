<?php
// ---
// slug: use-doctrine-orm-filters
// name: Use Doctrine Filters
// executable: true
// ---

// Doctrine ORM features [a filter system](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/filters.html) that allows the developer to add SQL to the conditional clauses of queries, regardless of the place where the SQL is generated (e.g. from a DQL query, or by loading associated entities).
//
// These are applied on collections and items and therefore are incredibly useful.
//
// The following information, specific to Doctrine filters in Symfony, is based upon [a great article posted on Michaël Perrin's blog](http://blog.michaelperrin.fr/2014/12/05/doctrine-filters/).
//
// Suppose we have a `User` entity and an `Book` entity related to the `User` one. A user should only see his books and no one else's.

namespace App\Entity {
    use ApiPlatform\Metadata\ApiFilter;
    use ApiPlatform\Metadata\ApiResource;
    use App\Attribute\UserAware;
    use App\Filter\UserFilter;
    use Doctrine\ORM\Mapping as ORM;

    /*
     * Create a User object to represent the current user.
     */
    #[ApiResource]
    #[ORM\Entity]
    class User
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\Column]
        public ?string $username = null;

        public function getId(): ?int
        {
            return $this->id;
        }
    }

    /*
     * Each Book is related to a User, supposedly allowed to authenticate.
     */
    #[ApiResource]
    #[ORM\Entity]
    /*
     * This entity is restricted by current user: only current user books will be shown (cf. UserFilter).
     */
    #[UserAware(userFieldName: 'user_id')]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\ManyToOne(User::class)]
        #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
        public User $user;

        #[ORM\Column]
        public ?string $title = null;

        public function getId(): ?int
        {
            return $this->id;
        }
    }
}

namespace App\Attribute {
    use Attribute;

    /*
     * The UserAware attribute restricts entities to the current user.
     */
    #[Attribute(Attribute::TARGET_CLASS)]
    final class UserAware
    {
        public ?string $userFieldName = null;
    }
}

namespace App\Filter {
    use App\Attribute\UserAware;
    use Doctrine\ORM\Mapping\ClassMetadata;
    use Doctrine\ORM\Query\Filter\SQLFilter;

    /*
     * The UserFilter adds a `AND user_id = :user_id` in the SQL query.
     */
    final class UserFilter extends SQLFilter
    {
        public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
        {
            /*
             * The Doctrine filter is called for any query on any entity.
             * Check if the current entity is "user aware" (marked with an attribute).
             */
            $userAware = $targetEntity->getReflectionClass()->getAttributes(UserAware::class)[0] ?? null;

            $fieldName = $userAware?->getArguments()['userFieldName'] ?? null;
            if ('' === $fieldName || is_null($fieldName)) {
                return '';
            }

            try {
                /*
                 * Don't worry, getParameter automatically escapes parameters
                 */
                $userId = $this->getParameter('id');
            } catch (\InvalidArgumentException $e) {
                /*
                 * No user ID has been defined
                 */
                return '';
            }

            if (empty($fieldName) || empty($userId)) {
                return '';
            }

            return sprintf('%s.%s = %s', $targetTableAlias, $fieldName, $userId);
        }
    }
}

namespace App\EventSubscriber {
    use App\Entity\User;
    use Doctrine\Persistence\ObjectManager;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\HttpKernel\KernelEvents;

    /*
     * Retrieve the current user id and set it as SQL query parameter.
     */
    final class UserAwareEventSubscriber implements EventSubscriberInterface
    {
        public function __construct(private readonly ObjectManager $em)
        {
        }

        public static function getSubscribedEvents(): array
        {
            return [
                KernelEvents::REQUEST => 'onKernelRequest',
            ];
        }

        public function onKernelRequest(): void
        {
            /*
             * You should retrieve the current user using the TokenStorage service.
             * In this example, the user is forced by username to keep this guide simple.
             */
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'jane.doe']);
            $filter = $this->em->getFilters()->enable('user_filter');
            $filter->setParameter('id', $user->getId());
        }
    }
}

 namespace App\DependencyInjection {

     use App\EventSubscriber\UserAwareEventSubscriber;
     use App\Filter\UserFilter;
     use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
     use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

     function configure(ContainerConfigurator $configurator) {
         $services = $configurator->services();
         $services->set(UserAwareEventSubscriber::class)
             ->args([service('doctrine.orm.default_entity_manager')])
             ->tag('kernel.event_subscriber')
         ;
         $configurator->extension('doctrine', [
             'orm' => [
                 'filters' => [
                     'user_filter' => [
                         'class' => UserFilter::class,
                         'enabled' => true,
                     ],
                 ],
             ],
         ]);

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
            $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(255) NOT NULL)');
            $this->addSql('CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, user_id INTEGER NOT NULL, FOREIGN KEY (user_id) REFERENCES user (id))');
        }
    }
}

namespace App\Fixtures {
    use App\Entity\Book;
    use App\Entity\User;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;
    use function Zenstruck\Foundry\anonymous;

    final class BookFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            $userFactory = anonymous(User::class);
            $johnDoe = $userFactory->create(['username' => 'john.doe']);
            $janeDoe = $userFactory->create(['username' => 'jane.doe']);

            $bookFactory = anonymous(Book::class);
            $bookFactory->many(10)->create([
                'title' => 'title',
                'user' => $johnDoe
            ]);
            $bookFactory->many(10)->create([
                'title' => 'title',
                'user' => $janeDoe
            ]);
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
            $response = static::createClient()->request('GET', '/books.jsonld');

            $this->assertResponseIsSuccessful();
            $this->assertMatchesResourceCollectionJsonSchema(Book::class, '_api_/books{._format}_get_collection', 'jsonld');
            $this->assertNotSame(0, $response->toArray(false)['hydra:totalItems'], 'The collection is empty.');
            $this->assertJsonContains([
                'hydra:totalItems' => 10,
            ]);
        }
    }
}

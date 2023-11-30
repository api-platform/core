<?php
// ---
// slug: handle-links
// name: Handle doctrine links
// position: 15
// tags: doctrine, expert
// executable: true
// ---

// When using subresources with doctrine, API Platform tries to handle your links,
// and the algorithm sometimes overcomplicates SQL queries.

namespace App\Entity {
    use ApiPlatform\Doctrine\Orm\State\Options;
    use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\Link;
    use Doctrine\ORM\Mapping as ORM;
    use Doctrine\ORM\QueryBuilder;

    // To get around that, you can hook into the link management with an [ORM StateOption](/docs/reference/Doctrine/Orm/State/Options/)
    #[GetCollection(
        uriTemplate: '/company/{companyId}/employees',
        uriVariables: ['companyId' => new Link(fromClass: Company::class, toProperty: 'company')],
        // The `handleLinks` option takes a service name implementing the [LinksHandlerInterface](/docs/reference/Doctrine/Orm/State/LinksHandlerInterface) or a callable.
        stateOptions: new Options(handleLinks: [Employee::class, 'handleLinks'])
    )]
    #[Get('/company/{companyId}/employees/{id}')]
    #[ORM\Entity]
    class Employee
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id;

        #[ORM\Column]
        public string $name;

        #[ORM\ManyToOne(targetEntity: Company::class)]
        public ?Company $company;

        public function getId()
        {
            return $this->id;
        }

        // This function gets called in our generic ItemProvider or CollectionProvider, the idea is to create the WHERE clause
        // to get the correct data. You can also perform joins or whatever SQL clause you need:
        public static function handleLinks(QueryBuilder $queryBuilder, array $uriVariables, QueryNameGeneratorInterface $queryNameGenerator, array $context): void
        {
            $queryBuilder
                ->andWhere($queryBuilder->getRootAliases()[0].'.company = :companyId')
                ->setParameter('companyId', $uriVariables['companyId']);
        }
    }

    #[ORM\Entity]
    #[ApiResource]
    class Company
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id;

        #[ORM\Column]
        public string $name;
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        // Persistence is automatic, you can try to create or read data:
        return Request::create('/company/1/employees', 'GET');
    }
}

namespace DoctrineMigrations {
    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;

    final class Migration extends AbstractMigration
    {
        public function up(Schema $schema): void
        {
            $this->addSql('CREATE TABLE company (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL);');
            $this->addSql('CREATE TABLE employee (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, company_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, CONSTRAINT FK_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE);
');
            $this->addSql('CREATE INDEX FK_COMPANY ON employee (company_id)');
        }
    }
}

namespace App\Fixtures {
    use App\Entity\Company;
    use App\Entity\Employee;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;

    use function Zenstruck\Foundry\anonymous;
    use function Zenstruck\Foundry\faker;
    use function Zenstruck\Foundry\repository;

    final class BookFixtures extends Fixture
    {
        public function load(ObjectManager $manager): void
        {
            $companyFactory = anonymous(Company::class);
            $companyRepository = repository(Company::class);
            if ($companyRepository->count()) {
                return;
            }

            $companyFactory->many(1)->create(fn () => [
                'name' => faker()->company(),
            ]);

            $employeeFactory = anonymous(Employee::class);
            $employeeFactory->many(10)->create(fn () => [
                'name' => faker()->name(),
                'company' => $companyRepository->first(),
            ]
            );
        }
    }
}

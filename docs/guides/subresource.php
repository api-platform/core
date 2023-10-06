<?php
// ---
// slug: subresource
// name: Declare a subresource
// position: 8
// tags: design, expert
// executable: true
// ---

// # Subresource
//
// In API Platform, a subresource is an alternate way to reach a Resource.

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\Link;
    use ApiPlatform\Metadata\Post;
    use Doctrine\ORM\Mapping as ORM;

    // This is our standard Resource, we only allow the Post operation.
    #[ApiResource(
        operations: [new Post()]
    )]
    // To read this resource, we decided that it is only available through a Company.
    #[ApiResource(
        uriTemplate: '/companies/{companyId}/employees/{id}',
        // [URI Variables](/docs/core/subresources/#uri-variables-configuration) allow to configure
        // how API Platform links resources together.
        uriVariables: [
            'companyId' => new Link(fromClass: Company::class, toProperty: 'company'),
            'id' => new Link(fromClass: Employee::class),
        ],
        operations: [new Get()]
    )]
    #[ApiResource(
        uriTemplate: '/companies/{companyId}/employees',
        uriVariables: [
            'companyId' => new Link(fromClass: Company::class, toProperty: 'company'),
        ],
        operations: [new GetCollection()]
    )]
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
            $this->addSql('CREATE TABLE employee (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, company_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, CONSTRAINT FK_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE);');
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

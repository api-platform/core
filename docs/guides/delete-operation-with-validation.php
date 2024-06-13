<?php
// ---
// slug: delete-operation-with-validation
// name: Delete operation with validation
// position: 20
// tags: validation, expert
// executable: true
// ---

// Let's add a [custom Constraint](https://symfony.com/doc/current/validation/custom_constraint.html).

namespace App\Validator {
    use Symfony\Component\Validator\Constraint;

    #[\Attribute]
    class AssertCanDelete extends Constraint
    {
        public string $message = 'For whatever reason we denied removal of this data.';
        public string $mode = 'strict';

        public function getTargets(): string
        {
            return self::CLASS_CONSTRAINT;
        }
    }
}

// And a custom validator following Symfony's naming conventions.

namespace App\Validator {
    use Symfony\Component\Validator\Constraint;
    use Symfony\Component\Validator\ConstraintValidator;

    class AssertCanDeleteValidator extends ConstraintValidator
    {
        public function validate(mixed $value, Constraint $constraint): void
        {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}

namespace App\Entity {
    use ApiPlatform\Metadata\Delete;
    use ApiPlatform\Validator\Exception\ValidationException;
    use App\Validator\AssertCanDelete;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity]
    #[Delete(
        // By default, validation is not triggered on a DELETE operation, let's activate it.
        validate: true,
        // Just as with serialization we can add [validation groups](/docs/core/validation/#using-validation-groups).
        validationContext: ['groups' => ['deleteValidation']],
        exceptionToStatus: [ValidationException::class => 403]
    )]
    // Here we use the previously created constraint on the class directly.
    #[AssertCanDelete(groups: ['deleteValidation'])]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\Column]
        public string $title = '';

        public function getId()
        {
            return $this->id;
        }
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create(uri: '/books/1', method: 'DELETE', server: ['CONTENT_TYPE' => 'application/ld+json']);
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

            $bookFactory->many(10)->create(
                fn () => [
                    'title' => faker()->name(),
                ]
            );
        }
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

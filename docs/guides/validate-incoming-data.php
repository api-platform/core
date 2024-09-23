<?php
// ---
// slug: validate-incoming-data
// name: Validate incoming data
// executable: true
// position: 5
// tags: validation
// ---

// When processing the incoming request, when creating or updating content, API-Platform will validate the
// incoming content. It will use the [Symfony's validator](https://symfony.com/doc/current/validation.html).
//
// API Platform takes care of validating the data sent to the API by the client (usually user data entered through forms).
// By default, the framework relies on the powerful [Symfony Validator Component](http://symfony.com/doc/current/validation.html) for this task, but you can replace it with your preferred validation library such as the [PHP filter extension](https://www.php.net/manual/en/intro.filter.php) if you want to.
// Validation is called when handling a POST, PATCH, PUT request as follows :

// graph LR
// Request --> Deserialization
// Deserialization --> Validation
// Validation --> Persister
// Persister --> Serialization
// Serialization --> Response

// In this guide we're going to use [Symfony's built-in constraints](http://symfony.com/doc/current/reference/constraints.html) and a [custom constraint](http://symfony.com/doc/current/validation/custom_constraint.html). Let's start by shaping our to-be-validated resource:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    // A custom constraint.
    use App\Validator\Constraints\MinimalProperties;
    use Doctrine\ORM\Mapping as ORM;
    // Symfony's built-in constraints
    use Symfony\Component\Validator\Constraints as Assert;

    /**
     * A product.
     */
    #[ORM\Entity]
    #[ApiResource]
    class Product
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\Column]
        #[Assert\NotBlank]
        public string $name;

        /**
         * @var string[] Describe the product
         */
        #[MinimalProperties]
        #[ORM\Column(type: 'json')]
        public $properties;

        public function getId(): ?int
        {
            return $this->id;
        }
    }
}

// The `MinimalProperties` constraint will check that the `properties` data holds at least two values: description and price.
// We start by creating the constraint:

namespace App\Validator\Constraints {
    use Symfony\Component\Validator\Constraint;

    #[\Attribute]
    class MinimalProperties extends Constraint
    {
        public $message = 'The product must have the minimal properties required ("description", "price")';
    }
}

// Then the validator following [Symfony's naming conventions](https://symfony.com/doc/current/validation/custom_constraint.html#creating-the-validator-itself)

namespace App\Validator\Constraints {
    use Symfony\Component\Validator\Constraint;
    use Symfony\Component\Validator\ConstraintValidator;

    final class MinimalPropertiesValidator extends ConstraintValidator
    {
        public function validate($value, Constraint $constraint): void
        {
            if (!\array_key_exists('description', $value) || !\array_key_exists('price', $value)) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
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
            $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, properties CLOB NOT NULL)');
        }
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create(
            uri: '/products',
            method: 'POST',
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            content: '{"name": "test", "properties": {"description": "Test product"}}'
        );
    }
}

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testValidation(): void
        {
            $response = static::createClient()->request(method: 'POST', url: '/products', options: [
                'json' => ['name' => 'test', 'properties' => ['description' => 'foo']],
                'headers' => ['content-type' => 'application/ld+json'],
            ]);

            // If the data submitted by the client is invalid, the HTTP status code will be set to 422 Unprocessable Entity and the response's body will contain the list of violations serialized in a format compliant with the requested one. For instance, a validation error will look like the following if the requested format is JSON-LD (the default):
            // ```json
            // {
            //   "@context": "/contexts/ConstraintViolationList",
            //   "@type": "ConstraintViolationList",
            //   "title": "An error occurred",
            //   "description": "properties: The product must have the minimal properties required (\"description\", \"price\")",
            //   "violations": [
            //     {
            //       "propertyPath": "properties",
            //       "message": "The product must have the minimal properties required (\"description\", \"price\")"
            //     }
            //   ]
            //  }
            // ```
            $this->assertResponseStatusCodeSame(422);
            $this->assertJsonContains([
                'description' => 'properties: The product must have the minimal properties required ("description", "price")',
                'title' => 'An error occurred',
                'violations' => [
                    ['propertyPath' => 'properties', 'message' => 'The product must have the minimal properties required ("description", "price")'],
                ],
            ]);
        }
    }
}

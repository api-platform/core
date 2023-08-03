<?php
// --- 
// slug: use-validation-groups
// name: Use Validation Groups
// position: 3 
// tags: validation
// ---

// # Validing incoming data
// When processing the incoming request, when creating or updating content, API-Platform will validate the
// incoming content. It will use the [Symfony's validator](https://symfony.com/doc/current/validation.html).
//
// API Platform takes care of validating the data sent to the API by the client (usually user data entered through forms). 
// By default, the framework relies on the powerful [Symfony Validator Component](http://symfony.com/doc/current/validation.html) for this task, but you can replace it with your preferred validation library such as the [PHP filter extension](https://www.php.net/manual/en/intro.filter.php) if you want to.
// Validation is called when handling a POST, PATCH, PUT request as follows :

//graph LR
//Request --> Deserialization
//Deserialization --> Validation
//Validation --> Persister
//Persister --> Serialization
//Serialization --> Response

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
            if (!array_diff(['description', 'price'], $value)) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }
    }
}

//If the data submitted by the client is invalid, the HTTP status code will be set to 422 Unprocessable Entity and the response's body will contain the list of violations serialized in a format compliant with the requested one. For instance, a validation error will look like the following if the requested format is JSON-LD (the default):
// ```json
// {
//   "@context": "/contexts/ConstraintViolationList",
//   "@type": "ConstraintViolationList",
//   "hydra:title": "An error occurred",
//   "hydra:description": "properties: The product must have the minimal properties required (\"description\", \"price\")",
//   "violations": [
//     {
//       "propertyPath": "properties",
//       "message": "The product must have the minimal properties required (\"description\", \"price\")"
//     }
//   ]
//  }
// ```
//
// Take a look at the [Errors Handling guide](errors.md) to learn how API Platform converts PHP exceptions like validation
// errors to HTTP errors.

<?php
// ---
// slug: validate-data-on-a-delete-operation
// name: Validate Data on a Delete Operation
// position: 99
// ---

// Let's add a [custom Constraint](https://symfony.com/doc/current/validation/custom_constraint.html).
namespace App\Validator {
    use Symfony\Component\Validator\Constraint;
    use Symfony\Component\Validator\ConstraintValidator;

    #[\Attribute]
    class AssertCanDelete extends Constraint
    {
        public string $message = 'The string "{{ string }}" contains an illegal character: it can only contain letters or numbers.';
        public string $mode = 'strict';
    }
}

// And a custom validator following Symfony's naming conventions.
namespace App\Validator {

    use Symfony\Component\Validator\ConstraintValidator;
    use Symfony\Component\Validator\Constraint;

    class AssertCanDeleteValidator extends ConstraintValidator
    {
        public function validate(mixed $value, Constraint $constraint)
        {
            /* TODO: Implement validate() method. */
        }
    }
}


// By default, validation is not triggered during a DELETE operation and we need to trigger validation manually.
namespace App\ApiResource {
    use ApiPlatform\Metadata\Delete;
    use App\State\BookRemoveProcessor;
    use App\Validator\AssertCanDelete;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity]
    #[Delete(validationContext: ['groups' => ['deleteValidation']], processor: BookRemoveProcessor::class)]
    // Here we use the previously created constraint on the class directly.
    #[AssertCanDelete(groups: ['deleteValidation'])]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\Column]
        public string $title = '';
    }
}

// Then, we will trigger the validation within a processor.
// the removal into the Database.
namespace App\State {
    use ApiPlatform\Doctrine\Common\State\RemoveProcessor as DoctrineRemoveProcessor;
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ProcessorInterface;
    use ApiPlatform\Validator\ValidatorInterface;
    use Symfony\Component\DependencyInjection\Attribute\Autowire;

    class BookRemoveProcessor implements ProcessorInterface
    {
        public function __construct(
            // We're decorating API Platform's Doctrine processor to persist the removal.
            #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
            private DoctrineRemoveProcessor $doctrineProcessor,
            private ValidatorInterface $validator,
        ) {
        }

        public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
        {
            // First step is to trigger Symfony's validation.
            $this->validator->validate($data, ['groups' => ['deleteValidation']]);
            // Then we persist the data.
            $this->doctrineProcessor->process($data, $operation, $uriVariables, $context);
        }
    }
}

// TODO move this to reference somehow
// This operation uses a Callable as group so that you can vary the Validation according to your dataset
// new Get(validationContext: ['groups' =>])
// ## Sequential Validation Groups
// If you need to specify the order in which your validation groups must be tested against, you can use a [group sequence](http://symfony.com/doc/current/validation/sequence_provider.html).

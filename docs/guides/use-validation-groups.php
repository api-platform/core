<?php
// --- 
// slug: validate-incoming-data
// name: Validate incoming Data
// position: 3 
// executable: true
// ---

// ## Using Validation Groups
// Without specific configuration, the default validation group is always used, but this behavior is customizable: the framework
// is able to leverage Symfony's [validation groups](https://symfony.com/doc/current/validation/groups.html).
namespace App\ApiResource {
    use App\Validator\MySequencedGroup;
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\Post;
    use Symfony\Component\Validator\Constraints as Assert;

    // We can specify groups on the [ApiResource::validationContext](http://localhost:3000/reference/Metadata/ApiResource#validationContext) property.
    #[ApiResource(
        validationContext: ['groups' => ['a', 'b']],
        operations: [
            // When configured on a specific operation the configuration takes precedence over the one declared on the ApiResource. 
            // You can use a [callable](https://www.php.net/manual/en/language.types.callable.php) instead of strings.
            new Get(validationContext: ['groups' => [Book::class, 'validationGroups']]),
            new GetCollection(),
            // You sometimes want to specify in which order groups must be tested against. On the Post operation, we use a Symfony service 
            // to use a [group sequence](http://symfony.com/doc/current/validation/sequence_provider.html).
            new Post(validationContext: ['groups' => MySequencedGroup::class])
        ]
    )]
    final class Book
    {
        #[Assert\NotBlank(groups: ['a'])]  
        public string $name;

        #[Assert\NotNull(groups: ['b'])] 
        public string $author;

        /**
         * Return dynamic validation groups.
         *
         * @param self $book Contains the instance of Book to validate.
         *
         * @return string[]
         */
        public static function validationGroups(self $book)
        {
            return ['a'];
        }
    }
}

namespace App\Validator {
    use Symfony\Component\Validator\Constraints\GroupSequence;

    final class MySequencedGroup
    {
        public function __invoke(): GroupSequence
        {
            return new GroupSequence(['a', 'b']); // now, no matter which is first in the class declaration, it will be tested in this order.
        }
    }
}

// To go further, read the guide on [Validating data on a Delete operation](./validate-data-on-a-delete-operation)

<?php
// --- 
// slug: secure-a-resource-with-custom-voters
// name: Secure a Resource with Custom Voters
// position: 10
// executable: true
// ---

namespace App\Security\Voter {
    use App\Entity\Book;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Authorization\Voter\Voter;
    use Symfony\Component\Security\Core\Security;

    // First let's create a Voter, for example using the `bin/console make:voter` command:
    class BookVoter extends Voter
    {
        private $security = null;

        public function __construct(Security $security)
        {
            $this->security = $security;
        }

        protected function supports($attribute, $subject): bool
        {
            // It supports several attributes related to our Resource access control.
            $supportsAttribute = in_array($attribute, ['BOOK_CREATE', 'BOOK_READ', 'BOOK_EDIT', 'BOOK_DELETE']);
            $supportsSubject = $subject instanceof Book;

            return $supportsAttribute && $supportsSubject;
        }

        /**
        * @param string $attribute
        * @param Book $subject
        * @param TokenInterface $token
        * @return bool
        */
        protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
        {
            /** ... check if the user is anonymous ... **/

            switch ($attribute) {
                case 'BOOK_CREATE':
                    if ( $this->security->isGranted(Role::ADMIN) ) { return true; }  // only admins can create books
                    break;
                case 'BOOK_READ':
                    /** ... other autorization rules ... **/
            }

            return false;
        }
    }
}

namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Delete;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\GetCollection;
    use ApiPlatform\Metadata\Post;
    use ApiPlatform\Metadata\Put;

    #[ApiResource(security: "is_granted('ROLE_USER')")]
    // We can then use the `is_granted` expression with our access control attributes:
    #[Get(security: "is_granted('BOOK_READ', object)")]
    #[Put(security: "is_granted('BOOK_EDIT', object)")]
    #[Delete(security: "is_granted('BOOK_DELETE', object)")]
    // On a collection, you need to [implement a Provider](provide-the-resource-state) to filter the collection manually.
    #[GetCollection]
    // `object` is empty uppon creation, we use `securityPostDenormalize` to get the denormalized object. 
    #[Post(securityPostDenormalize: "is_granted('BOOK_CREATE', object)")]
    class Book
    {
        // ...
    }
}

<?php
// --- 
// slug: secure-a-resource-access
// name: Secure a Resource Access
// position: 4
// executable: true
// ---

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Security\User;

// We start by securing access to this resource to logged in users. 
#[ApiResource(security: "is_granted('ROLE_USER')")]
#[Get]
// To create a new resource using the Post operation, a user has to belong to the `ROLE_ADMIN` role.
// We also customize the "Access Denied." message with the `securityMessage` property. 
#[Post(security: "is_granted('ROLE_ADMIN')", securityMessage: "Only an admin has access to that operation.")]
// If a user **owns** the Book or has the `ROLE_ADMIN` role, he can update the object using the Put operation. Here we're
// using the `object`'s owner. The supported variables within the access control expression are:
//   - user: the current logged in object, if any
//   - object: contains the value submitted by the user 
//   - request: the current Request object
#[Put(security: "is_granted('ROLE_ADMIN') or object.owner == user")]
#[GetCollection]
#[ORM\Entity]
class Book
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    public string $title;

    #[ORM\ManyToOne]
    public User $owner;

    // The security attribute is also available on [ApiProperty::security](/reference/Metadata/ApiProperty#security).
    // Access control checks in the security attribute are always executed before the denormalization step. 
    // If you want the object after denormalization, use `securityPostDenormalize`. Using this access control variables have:
    //   - object: the object after denormalization
    //   - previous_object: a clone of the object before modifications were made
    /**
     * @var string Property viewable and writable only by users with ROLE_ADMIN
     */
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')", securityPostDenormalize: "is_granted('UPDATE', object)")]
    public string $adminOnlyProperty;
}

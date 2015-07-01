# FOSUser Bundle integration

This bundle is shipped with a bridge for the [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle). If the FOSUserBundle is enabled, this bridges registers to the persist, update and delete events to pass user objects to the UserManager, before redispatching the event. 

## Creating a `User` entity with serialization groups

Here's an example of declaration of a doctrine ORM User class. As shown you can use serialization groups to hide properties like plainPassword and password (only in read). The properties shown are handled with the normalizationContext, while the properties you can modify are handled with DenormalizationContext.

First register the following service:

```yaml
# app/config/services.yml

resource.user:
        parent:    "api.resource"
        arguments: [ "AppBundle\Entity\User" ]
        calls:
            -      method:    "initNormalizationContext"
                   arguments: [ { groups: [ "user_read" ] } ]
            -      method:    "initDenormalizationContext"
                   arguments: [ { groups: [ "user_write" ] } ]
        tags:      [ { name: "api.resource" } ]
```

Then create your User entity with serialization groups:

```php
<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 *
 * @UniqueEntity("email")
 * @UniqueEntity("username")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string The username of the author.
     *
     * @Groups({"user_read", "user_write"})
     */
    protected $username;

    /**
     * @var string The email of the user.
     *
     * @Groups({"user_read", "user_write"})
     */
    protected $email;

    /**
     * @var string Plain password. Used for model validation. Must not be persisted.
     *
     * @Groups({"user_write"})
     */
    protected $plainPassword;

    /**
     * @var boolean Shows that the user is enabled
     *
     * @Groups({"user_read", "user_write"})
     */
    protected $enabled;

    /**
     * @var array Array, role(s) of the user
     *
     * @Groups({"user_read", "user_write"})
     */
    protected $roles;
}

```

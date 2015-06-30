# FOSUser Bundle integration

This bundle is shipped with a bridge for the [FOSUserBundle](https://github.com/FriendsOfSymfony/FOSUserBundle). If the FOSUserBundle is enabled, this bridges registers to the persist, update and delete events to pass user objects to the UserManager, before redispatching the event. 

### Example to create User with serialization groups :

First register the following service :

```
# app/config/services.yml

resource.user:
        parent:    "api.resource"
        arguments: [ "AppBundle\\Entity\\User" ]
        calls:
            -      method:    "initNormalizationContext"
                   arguments: [ { groups: [ "user_read" ] } ]
            -      method:    "initDenormalizationContext"
                   arguments: [ { groups: [ "user_write" ] } ]
        tags:      [ { name: "api.resource" } ]
```

Then create your User entity with serialization groups :

```
<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
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
     * @Groups({"user_read", "user_write", "article_read", "category_read"})
     */
    protected $username;

    /**
     * @var string The email of the user.
     *
     * @Groups({"user_read", "user_write"})
     */
    protected $email;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
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

    /**
     * @var BlogPosting[] Collection of BlogPosting.
     *
     * @ORM\OneToMany(targetEntity="BlogPosting", mappedBy="author")
     * @Groups({"user_read"})
     */
    private $blog_postings;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->blog_postings = new ArrayCollection();
    }

 // getters and setters ...
```

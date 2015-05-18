# The event system

DunglasApiBundle leverages the Symfony Event Dispatcher to provide a powerful event system triggered in the object lifecycle.
Here is the list of available events:

## Retrieving list

- `api.retrieve_list` (`Dunglas\ApiBundle\Event::RETRIEVE_LIST`): occurs after the retrieving of an object list during a `GET` request on a collection.

## Retrieving item

- `api.retrieve` (`Dunglas\ApiBundle\Event::RETRIEVE_LIST`): after the retrieving of an object during a `GET` request on an item.

## Creating item

- `api.pre_create_validation` (`Dunglas\ApiBundle\Event::PRE_CREATE_VALIDATION`): occurs before the object validation during a `POST` request.
- `api.pre_create` (`Dunglas\ApiBundle\Event::PRE_CREATE`): occurs after the object validation and before its persistence during a `POST` request
- `api.post_create` (`Dunglas\ApiBundle\Event::POST_CREATE`): event occurs after the object persistence during `POST` request

## Updating item

- `api.pre_update_validation` (`Dunglas\ApiBundle\Event::PRE_UPDATE_VALIDATION`): event occurs before the object validation during a `PUT` request.
- `api.pre_update` (`Dunglas\ApiBundle\Event::PRE_UPDATE`): occurs after the object validation and before its persistence during a `PUT` request
- `api.post_update` (`Dunglas\ApiBundle\Event::POST_UPDATE`): event occurs after the object persistence during a `PUT` request

## Deleting item

- `api.pre_delete` (`Dunglas\ApiBundle\Event::PRE_DELETE`): event occurs before the object deletion during a `DELETE` request
- `api.post_delete` (`Dunglas\ApiBundle\Event::POST_DELETE`): occurs after the object deletion during a `DELETE` request

## Registering an event listener

In the following example, we register an event listener that will be called every time after the creation of an object:

```php
<?php

// src/AppBundle/EventListener/MyEventListener.php

namespace AppBundle\EventListener;

use AppBundle\Entity\MyObject;
use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\JsonApiBundle\Event\DataEvent;

class MyEventListener
{
    /**
     * @param ObjectEvent $event
     */
    public function onPostCreate(ObjectEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof MyObject) {
            $resource = $event->getResource(); // Get the related instance of Dunglas\ApiBundle\Api\ResourceInterface

            // Do something awesome here
        }
    }
}
```

```yaml
# app/config/services.yml

services: 
    "my_event_listener":
        class:     "AppBundle\EventListener\MyEventListener"
        tags:
            - { name: "kernel.event_listener", event: "api.post_create", method: "onPostCreate"  }
```

Next chapter: [Resources](resources.md)
Previous chapter: [Validation](validation.md)

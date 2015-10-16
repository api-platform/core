# The event system

**Outdated documentation, only applicable to 1.0.0 beta 3 and below. See https://github.com/dunglas/DunglasApiBundle/pull/191**

DunglasApiBundle leverages the [Symfony Event Dispatcher Component](http://symfony.com/doc/current/components/event_dispatcher/index.html)
to provide a powerful event system triggered in the object lifecycle.

Additionally to [events triggered by the Symfony HTTP Kernel](http://symfony.com/doc/current/components/http_kernel/introduction.html#creating-an-event-listener)
and [by Doctrine ORM](http://doctrine-orm.readthedocs.org/en/latest/reference/events.html#reference-events-lifecycle-events)
(if you use it), DunglasApiBundle comes with its own set of events occurring during the handling of the request:

## Retrieving list

- `api.retrieve_list` (`Dunglas\ApiBundle\Event\Events::RETRIEVE_LIST`): occurs after the retrieving of an object list during a `GET` request on a collection.

## Retrieving item

- `api.retrieve` (`Dunglas\ApiBundle\Event\Events::RETRIEVE_LIST`): after the retrieving of an object during a `GET` request on an item.

## Creating item

- `api.pre_create_validation` (`Dunglas\ApiBundle\Event\Events::PRE_CREATE_VALIDATION`): occurs before the object validation during a `POST` request.
- `api.pre_create` (`Dunglas\ApiBundle\Event\Events::PRE_CREATE`): occurs after the object validation and before its persistence during a `POST` request.
- `api.post_create` (`Dunglas\ApiBundle\Event\Events::POST_CREATE`): event occurs after the object persistence during `POST` request.

## Updating item

- `api.pre_update_validation` (`Dunglas\ApiBundle\Event\Events::PRE_UPDATE_VALIDATION`): event occurs before the object validation during a `PUT` request.
- `api.pre_update` (`Dunglas\ApiBundle\Event\Events::PRE_UPDATE`): occurs after the object validation and before its persistence during a `PUT` request.
- `api.post_update` (`Dunglas\ApiBundle\Event\Events::POST_UPDATE`): event occurs after the object persistence during a `PUT` request.

## Deleting item

- `api.pre_delete` (`Dunglas\ApiBundle\Event\Events::PRE_DELETE`): event occurs before the object deletion during a `DELETE` request.
- `api.post_delete` (`Dunglas\ApiBundle\Event\Events::POST_DELETE`): occurs after the object deletion during a `DELETE` request.

## JSON-LD context builder

- `api.jsonld.context_builder` (`Dunglas\ApiBundle\JsonLd\Event\Events::CONTEXT_BUILDER`): occurs after the initialization of the context and lets you extend the context. Event object is an instance of Dunglas\ApiBundle\JsonLd\Event\ContextBuilderEvent.

## Registering an event listener

In the following example, we register an event listener that will be called every time after the creation of an object:

```php
<?php

// src/AppBundle/EventListener/MyEventListener.php

namespace AppBundle\EventListener;

use AppBundle\Entity\MyObject;
use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\ApiBundle\Event\DataEvent;

class MyEventListener
{
    /**
     * @param DataEvent $event
     */
    public function onPostCreate(DataEvent $event)
    {
        $data = $event->getData();

        if ($data instanceof MyObject) {
            $resource = $event->getResource(); // Get the related instance of Dunglas\ApiBundle\Api\ResourceInterface

            // Do something awesome here
        }
    }
}
```

```yaml
# app/config/services.yml

services:
    my_event_listener:
        class: "AppBundle\EventListener\MyEventListener"
        tags:  [ { name: "kernel.event_listener", event: "api.post_create", method: "onPostCreate" } ]
```

Previous chapter: [Validation](validation.md)<br>
Next chapter: [Resources](resources.md)

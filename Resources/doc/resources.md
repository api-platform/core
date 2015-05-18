# Resources

THe default `Resource` class provided by the bundle is sufficient for small projects. If your app grows, using custom resources
can become necessary.

## Using a custom `Resource` class

When the size of your services definition start to grow, it is useful to create custom resources instead of using the default
one. To do so, the `Dunglas\ApiBundle\Api\ResourceInterface` interface must be implemented.

```php
<?php

namespace AppBundle\Api;

use Dunglas\ApiBundle\Api\ResourceInterface;

class MyCustomResource implements ResourceInterface
{
    public function getEntityClass()
    {
        return 'AppBundle\Entity\MyCustomOne';
    }
    
    public function getItemOperations() {
        return [
            new MyItemOperation();
        ];
    }
    
    public function getCollectionOperations()
    {
        return [
            new MyCollectionOperation();
        ];
    }

    public function getFilters()
    {
        return [];
    }
    
    public function getNormalizationContext()
    {
        return [];
    }

    public function getNormalizationGroups()
    {
        return null;
    }
    
    public function getDenormalizationContext()
    {
        return [];
    }

    public function getDenormalizationGroups()
    {
        return null;
    }

    public function getValidationGroups()
    {
        return null;
    }

    public function getShortName()
    {
        return 'MyCustomOne';
    }
}
```

The service definition can now be simplified:

```yaml
services:
    resource.product:
        parent: "api.resource"
        class:  "AppBundle\Api\MyCustomResource"
        tags:   [ { name: "api.resource" } ]
```

Next chapter: [Controllers](controllers.md)
Previous chapter: [The event system](the-event-system.md)

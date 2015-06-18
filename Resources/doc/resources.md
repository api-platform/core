# Resources

The default `Resource` class provided by the bundle is sufficient for small projects. If your app grows, using custom resources
can become necessary.

## Using a custom `Resource` class

When the size of your services definition start to grow, it is useful to create custom resources instead of using the default
one. To do so, the `Dunglas\ApiBundle\Api\ResourceInterface` interface must be implemented.

```php
<?php

// src/AppBundle/Api/MyCustomResource.php

namespace AppBundle\Api;

use Dunglas\ApiBundle\Api\ResourceInterface;

class MyCustomResource implements ResourceInterface
{
    public function getEntityClass()
    {
        return 'AppBundle\Entity\MyCustomOne';
    }

    public function getShortName()
    {
        return 'MyCustomOne';
    }

    public function getItemOperations()
    {
        return [new Operation(new Route('/customs/{id}'), 'custom_item')];
    }

    public function getCollectionOperations()
    {
        return [new Operation(new Route('/customs'), 'custom_collection')];
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
        return;
    }

    public function getDenormalizationContext()
    {
        return [];
    }

    public function getDenormalizationGroups()
    {
        return;
    }

    public function getValidationGroups()
    {
        return;
    }

    public function isPaginationEnabledByDefault()
    {
        return false;
    }

    public function isClientAllowedToEnablePagination()
    {
        return false;
    }

    public function getItemsPerPageByDefault()
    {
        return 0.;
    }

    public function isClientAllowedToChangeItemsPerPage()
    {
        return false;
    }

    public function getEnablePaginationParameter()
    {
        return '';
    }

    public function getPageParameter()
    {
        return '';
    }

    public function getItemsPerPageParameter()
    {
        return '';
    }
}
```

The service definition can now be simplified:

```yaml
services:
    custom_resource:
        parent: "api.resource"
        class:  "AppBundle\Api\MyCustomResource"
        tags:   [ { name: "api.resource" } ]
```

Previous chapter: [The event system](the-event-system.md)<br>
Next chapter: [Controllers](controllers.md)

# Controllers

The bundle provide a default controller class implementing CRUD operations: `Dunglas\ApiBundle\Controller\ResourceController`.
Basically this controller class extends the default controller class of the FrameworkBundle of Symfony providing implementations
of CRUD actions. It also provides convenient methods to retrieve the `Resource` class associated with the current request
and to serialize entities using normalizers provided by the bundle.

## Using a custom controller

When [the event system](6-the-event-system.md) is not enough, it's possible to use custom controllers.

Your custom controller should extend the `ResourceController` provided by this bundle.

Example of custom controller:

```php
<?php

// src/AppBundle/Controller/CustomController.php

namespace AppBundle\Controller;

use Dunglas\ApiBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;

class CustomController extends ResourceController
{
    # Customize the AppBundle:Custom:custom action
    public function getAction(Request $request, $id)
    {
        $this->get('logger')->info('This is my custom controller.');
        
        return parent::getAction($request, $id);
    }
}
```

Previous chapter: [Controllers](controllers.md)<br>
Next chapter: [Using external (JSON-LD) vocabularies](external-vocabularies.md)

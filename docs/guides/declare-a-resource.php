<?php
// --- 
// slug: declare-a-resource
// name: Declare a Resource
// position: 1
// executable: true
// tags: design
// homepage: true
// ---

// # Declare a Resource
// This class represents an API resource
namespace App\ApiResource;

// The `#[ApiResource]` attribute registers this class as an HTTP resource.
use ApiPlatform\Metadata\ApiResource;
// These are the list of HTTP operations we use to declare a "CRUD" (Create, Read, Update, Delete).
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Validator\Exception\ValidationException;

// Each resource has its set of Operations.
// Note that the uriTemplate may use the `id` variable which is our unique
// identifier on this `Book`.
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/books/{id}'),
        // The GetCollection operation returns a list of Books.
        new GetCollection(uriTemplate: '/books'),
        new Post(uriTemplate: '/books'),
        new Patch(uriTemplate: '/books/{id}'),
        new Delete(uriTemplate: '/books/{id}'),
    ],
    // This is a configuration that is shared accross every operations. More details are available at [ApiResource::exceptionToStatus](/reference/Metadata/ApiResource#exceptionToStatus).
    exceptionToStatus: [
        ValidationException::class => 422
    ]
)]
// If a property named `id` is found it is the property used in your URI template
// we recommend to use public properties to declare API resources.
class Book
{
    public string $id;
}
// Select the [next example](./hook-a-persistence-layer-with-a-processor) to see how to hook a persistence layer.

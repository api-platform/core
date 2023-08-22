<?php
// --- 
// slug: provide-the-resource-state
// name: Provide the Resource State
// position: 2
// executable: true
// tags: design, state
// ---

// # Provide the Resource State
// Our model is the same then in the previous guide ([Declare a Resource](./declare-a-resource). API Platform will declare
// CRUD operations if we don't declare them. 
namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use App\State\BookProvider;

    // We use a `BookProvider` as the [ApiResource::provider](/reference/Metadata/ApiResource#provider) option. 
    #[ApiResource(provider: BookProvider::class)]
    class Book
    {
        public string $id;
    }
}

namespace App\State {
    use ApiPlatform\Metadata\CollectionOperationInterface;
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ProviderInterface;
    use App\ApiResource\Book;

    // The BookProvider is where we retrieve the data in our persistence layer. 
    // In this provider we choose to handle the retrieval of a single Book but also a list of Books.
    final class BookProvider implements ProviderInterface
    {
        public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
        {
            if ($operation instanceof CollectionOperationInterface) {
                $book = new Book();
                $book->id = '1';
                // As an exercise you can edit the code and add a second book in the collection.
                return [$book];
            }

            $book = new Book();
            // The value at `$uriVariables['id']` is the one that matches the `{id}` variable of the **[URI template](/explanation/uri#uri-template)**.
            $book->id = $uriVariables['id'];
            return $book;
        }
    }
}



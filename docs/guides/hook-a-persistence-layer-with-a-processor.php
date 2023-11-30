<?php
// ---
// slug: hook-a-persistence-layer-with-a-processor
// name: Hook a Persistence Layer with a Processor
// executable: true
// position: 3
// tags: design
// ---

namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use App\State\BookProcessor;
    use App\State\BookProvider;

    // We use a `BookProcessor` as the [ApiResource::processor](/docs/reference/Metadata/ApiResource#processor) option.
    #[ApiResource(processor: BookProcessor::class, provider: BookProvider::class)]
    class Book
    {
        public function __construct(public string $id, public string $title)
        {
        }
    }
}

namespace App\State {
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ProcessorInterface;
    use ApiPlatform\State\ProviderInterface;
    use App\ApiResource\Book;
    use Ramsey\Collection\CollectionInterface;

    // The BookProcessor is where we can handle a persistence layer.
    // In this processor we're storing the JSON representation of the book in a file.
    final class BookProcessor implements ProcessorInterface
    {
        /**
         * @param Book $data
         */
        public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Book
        {
            $id = $uriVariables['id'] ?? $data->id;
            file_put_contents(sprintf('book-%s.json', $id), json_encode($data));

            return $data;
        }
    }

    final class BookProvider implements ProviderInterface
    {
        public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Book
        {
            if ($operation instanceof CollectionInterface) {
                throw new \RuntimeException('Not supported.');
            }

            $file = sprintf('book-%s.json', $uriVariables['id']);
            if (!file_exists($file)) {
                return null;
            }

            $data = json_decode(file_get_contents($file));

            return new Book($data->id, $data->title);
        }
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create(uri: '/books', method: 'POST', server: ['CONTENT_TYPE' => 'application/ld+json'], content: json_encode(['id' => '1', 'title' => 'API Platform rocks.']));
    }
}

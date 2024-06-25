<?php
// ---
// slug: error-provider
// name: Error provider to translate exception messages
// position: 7
// executable: true
// tags: design, state
// ---

namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\Operation;
    use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

    #[ApiResource(
        operations: [
            new Get(provider: Book::class.'::provide'),
        ],
    )]
    class Book
    {
        public function __construct(
            public readonly int $id = 1,
            public readonly string $name = 'Anon',
        ) {
        }

        public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
        {
            throw new BadRequestHttpException('something is not right');
        }
    }
}

namespace App\State {
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ApiResource\Error;
    use ApiPlatform\State\ProviderInterface;

    // Note that we need to replace the "api_platform.state.error_provider" service, this is done later in this guide.
    final class ErrorProvider implements ProviderInterface
    {
        public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
        {
            $request = $context['request'];
            if (!$request || !($exception = $request->attributes->get('exception'))) {
                throw new \RuntimeException();
            }

            /** @var \ApiPlatform\Metadata\HttpOperation $operation */
            $status = $operation->getStatus() ?? 500;
            // You don't have to use this, you can use a Response, an array or any object (preferably a resource that API Platform can handle).
            $error = Error::createFromException($exception, $status);

            // care about hiding informations as this can be a security leak
            if ($status >= 500) {
                $error->setDetail('Something went wrong');
            } else {
                // You can handle translation here with the [Translator](https://symfony.com/doc/current/translation.html)
                $error->setDetail(str_replace('something is not right', 'les calculs ne sont pas bons', $exception->getMessage()));
            }

            return $error;
        }
    }
}

// This is replacing the service, the "key" is important as this is the provider we
// will look for when handling an exception.

namespace App\DependencyInjection {
    use App\State\ErrorProvider;
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    function configure(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();
        $services->set('api_platform.state.error_provider')
            ->class(ErrorProvider::class)
            ->tag('api_platform.state_provider', ['key' => 'api_platform.state.error_provider']);
    }
}

namespace App\Tests {
    use ApiPlatform\Playground\Test\TestGuideTrait;
    use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

    final class BookTest extends ApiTestCase
    {
        use TestGuideTrait;

        public function testBookDoesNotExists(): void
        {
            static::createClient()->request('GET', '/books/1', options: ['headers' => ['accept' => 'application/ld+json']]);
            $this->assertResponseStatusCodeSame(400);
            $this->assertJsonContains([
                'detail' => 'les calculs ne sont pas bons',
            ]);
        }
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books/1.jsonld', 'GET');
    }
}

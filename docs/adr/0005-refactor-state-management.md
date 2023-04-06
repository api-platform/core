# Refactor State Management

* Status: proposed
* Deciders: @dunglas, @soyuka, @alanpoulain

## Context and Problem Statement

Since version 2.2, we support both REST and GraphQL API styles out of the box.
This leads to some code duplication to extract states from request bodies (write requests),
and to generate resource representations from states retrieved from the data source.

The REST susbystem uses [Symfony-specific kernel event listeners](https://api-platform.com/docs/core/events/#built-in-event-listeners)
while the GraphQL subsystem relies on [an ad-hoc resolver system](https://api-platform.com/docs/core/graphql/#workflow-of-the-resolvers).

Also, while it's possible [to use API Platform as a standalone PHP library](https://api-platform.com/docs/core/bootstrap/),
this requires writing boilerplate code doing mostly what is done in the Symfony-specific listeners.
As we're working on integrating API Platform with Laravel, the Laravel package will contain similar code to the one in the Symfony
event listeners too.

As Martin Fowler writes in the _Patterns of Enterprise Application Architecture_:

> There’s just one controller, so you can easily enhance its behavior at
runtime with decorators [Gang of Four](https://en.wikipedia.org/wiki/Design_Patterns). You can have decorators for
authentication, character encoding, internationalization, and so forth,
and add them using a configuration file or even while the server is
running. ([Alur et al.](http://www.corej2eepatterns.com/InterceptingFilter.htm) describe this approach in detail under the name
Intercepting Filter.)

For API Platform 3, we refactored the whole metadata susbsytem to be more flexible, powerful, and covering more use cases.
This led to the refactoring of the two main interfaces allowing to plug a data source in API Platform: the state provider and the state processor interfaces.

Leveraging these new interfaces, it should be possible to simplify the code base and to remove most code duplication by transforming most of the code currently
stored in the kernel event listeners and in the GraphQL resolvers in dedicated state processors and state providers.

## Decision Outcome

1. Move the logic currently stored in `ReadListener`, `DeserializeListener`, `DenyAccessListener`, `ValidateListener`, `WriteListener` and `SerializeListener` in classes implementing the `StateProcessorInterface` or `StateProviderInterface`. These classes will implement the decorator pattern. All classes will be composed to create a chain. The classes containing Symfony-specific and/or Doctrine-specific logic will then be able to be easily replaced by other implementations (Laravel, PSR-7, super-globals...).
2. Remove the corresponding listeners.
3. Replace `PlaceholderController` by a controller calling the main `StateProcessor` and `StateProvider` objects directly.
4. Remove the GraphQL resolvers, call the main `StateProcessor` and StateProvider objects directly.

Consenquently, transforming the raw request body (e.g. the raw JSON document) to a PHP data structure will be the responsibility of a processor.
The default one will use the Symfony Serializer component to do so, but this will give the opportunity to the user to replace this class by one using another Serializer if necessary.
This will also allow the user to access the raw body if necessary, and will enable a whole class of optimizations, extra validations (e.g. validating a raw JSON string against a JSON Schema) etc.
Similarly, transforming PHP data structures into strings to be stored in response bodies will now be the responsibility of a state provider.

This will reduce the weigth of the code base and improve the whole design of API Platform.

This new design will also replace what we currently call [the "DTO" feature](https://api-platform.com/docs/core/dto/): a "data transformer" will now be just another state provider or processor.

Finally, the `resumable()` method will be removed from these interfaces. The decorator pattern allows, by ordering the composed objects, to achieve the same result with more flexibility.

To help using API Platform without Symfony, we could provide factories building the correct chain of data providers and persisters without relying on the Symfony Dependency Injection Component.


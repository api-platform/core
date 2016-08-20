# Security tests

This directory contains a list of tests proving that API Platform enforces [OWASP's recommendations for REST APIs](https://www.owasp.org/index.php/REST_Security_Cheat_Sheet).
If you find a vulnerability in API Platform, please report it according to the procedure detailed in the [CONTRIBUTING.md](../../CONTRIBUTING.md)
file.

## Authentication and session management

Authentication and session management is delegated to the [Symfony Security component](http://symfony.com/doc/current/components/security.html).
This component has its own test suite.

## Authorization

Authorization is delegated to the [Symfony Security component](http://symfony.com/doc/current/components/security.html).
This component has its own test suite.

## Input validation

### Input validation 101

Input validation is delegated to the [Symfony Validator component](http://symfony.com/doc/current/components/validator.html)
(an implementation of the [JSR-303 Bean Validation specification](https://jcp.org/en/jsr/detail?id=303).
This component has its own test suite.

### Secure parsing

Parsing is delegated to the [Symfony Serializer component](http://symfony.com/doc/current/components/serializer.html).
This component has its own test suite.

### Strong typing

Strong typing is ensured by [our "strong typing" functional test suite](strong_typing.md) and [the unit tests of the `AbstractItemNormalizer`
class](../../tests/Serializer/AbstractItemNormalizerTest.php).

You might also be interested to see how extra attributes are ignored: [unknown_attributes.feature]

### Validate incoming content-types

Incoming content-types validation is ensured by [our "validate incoming content-types" functional test suite](validate_incoming_content-types.md) and [the unit tests of the `DeserializeListener`
class](../../tests/EventListener/DeserializeListenerTest.php).


To be continued.

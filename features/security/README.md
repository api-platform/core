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

Strong typing is ensured by [our "strong typing" functional test suite](strong_typing.feature) and [the unit tests of the `AbstractItemNormalizer`
class](../../tests/Serializer/AbstractItemNormalizerTest.php).

You might also be interested to see [how extra attributes are ignored](unknown_attributes.feature).

### Validate incoming content-types

Incoming content-types validation is ensured by [our "validate incoming content-types" functional test suite](validate_incoming_content-types.feature) and [the unit tests of the `DeserializeListener`
class](../../tests/EventListener/DeserializeListenerTest.php).

### Validate response types

Response type validation is ensured by [our "validate response types" functional test suite](validate_response_types.feature)
and [the unit tests of the `AddFormatListener` class](../../tests/EventListener/AddFormatListenerTest.php).

### XML input validation

XML parsing is delegated to the [Symfony Serializer component](http://symfony.com/doc/current/components/serializer.html).
This component has its own test suite.

### Framework-Provided validation

API Platform is shipped with the [Symfony Validator component](http://symfony.com/doc/current/components/validator.html),
one of the most popular framework validation in the world.

## Output encoding

### Send security headers

The sending of security headers is ensured by [our "send security headers" functional test suite](send_security_headers.feature)
and the unit tests of the [`RespondListener`](../../tests/EventListener/RespondListenerTest.php), [`ExceptionAction`](../../tests/Action/ExceptionActionTest.php)
and [`ValidationExceptionListener`](../../tests/Bridge/Symfony/Validator/EventListener/ValidationExceptionListenerTest.php).

### JSON encoding

API Platform relies on the [Symfony Serializer component](http://symfony.com/doc/current/components/serializer.html), to
encode JSON.
This component has its own test suite.

### XML encoding

API Platform relies on the [Symfony Serializer component](http://symfony.com/doc/current/components/serializer.html), to
encode XML.
This component has its own test suite.

## Cryptography

Cryptography for transit and storage should be enabled and properly configured on your servers depending of the nature of
you application.
API Platform natively supports both HTTPS (always recommended) and HTTP (for read-only public data only).

## Message Integrity

API Platform relies on the [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle),
for JWT support.
This bundle and the underlying [JSON Object Signing and Encryption library for PHP](https://github.com/namshi/jose) library have their own test suites.

## HTTP Return Code

Setting proper HTTP return codes is delegated to the [Symfony Security component](http://symfony.com/doc/current/components/security.html).
This component has its own test suite.

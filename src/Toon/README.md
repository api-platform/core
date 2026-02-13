# API Platform Toon Format Support

This component provides [Toon format](https://github.com/toon-format/spec) support for API Platform.

## Features

- Toon format encoder/decoder using [helgesverre/toon](https://packagist.org/packages/helgesverre/toon)
- Item and collection normalization
- Entrypoint support
- Compatible with both Symfony and Laravel

## Installation

```bash
composer require api-platform/toon
```

## Configuration

### Symfony

Add the format to your API Platform configuration:

```yaml
# config/packages/api_platform.yaml
api_platform:
    formats:
        toon: ['application/x-toon']
```

### Laravel

Add the format to your configuration:

```php
// config/api-platform.php
return [
    'formats' => [
        'toon' => ['application/x-toon'],
    ],
];
```

## About Toon Format

TOON (Token-Oriented Object Notation) is a line-oriented, indentation-based text format that encodes the JSON data model with minimal quoting. It's designed for compact representation of structured data, especially uniform object arrays.

Example:

```toon
user: Alice
score: 95
tags[3]: api,platform,toon
```

See the [official specification](https://github.com/toon-format/spec/blob/main/SPEC.md) for more details.

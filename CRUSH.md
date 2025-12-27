# API Platform Core Development

This document provides guidelines for developing on the API Platform core.

## Laravel development:

Everything goes inside `src/Laravel`.
Tests need to run at `src/Laravel/vendor/bin/phpunit`.

## Development Commands

- **Run all tests:**
  ```bash
  vendor/bin/phpunit
  ```

- **Run a single test file:**
  ```bash
  vendor/bin/phpunit tests/Path/To/YourTest.php
  ```

- **Lint files:**
  ```bash
  vendor/bin/php-cs-fixer fix --dry-run --diff
  ```

- **Fix linting issues:**
  ```bash
  vendor/bin/php-cs-fixer fix
  ```

- **Run static analysis:**
  ```bash
  vendor/bin/phpstan analyse
  ```

## Code Style

- **Standard:** Follow PSR-12 and the rules in `.php-cs-fixer.dist.php`.
- **Imports:** Use `use` statements for all classes, and group them by namespace.
- **Naming:**
    - Classes: `PascalCase`
    - Methods: `camelCase`
    - Variables: `camelCase`
- **Types:** Use strict types (`declare(strict_types=1);`) in all PHP files. Use type hints for all arguments and return types where possible.
- **Error Handling:** Use exceptions for error handling.


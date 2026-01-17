# Agent Instructions for API Platform Core Development

This document provides guidelines for AI agents working on the API Platform core codebase.

## Primary Responsibilities

Unless explicitly asked otherwise:

*   **Focus on Test Writing:** Your main responsibility is to write functional tests for new features or bug fixes.
*   **No Bug Fixing:** Do not attempt to fix bugs; only write tests to expose them or verify fixes.
*   **Project Context:** API Platform is a PHP framework supporting both Symfony and Laravel. The user will specify which framework (defaults to Symfony).
*   **Fixture Handling:** Avoid altering existing fixtures to prevent unintended side effects on other tests. Create new entities/DTOs/models with unique names. Business logic is secondary; focus on framework testing.
*   **No Test Execution by Default:** Do not run tests unless explicitly asked (to save context, as tests produce verbose output).
*   **No Git commit by default:** Do not commit unless explicitly asked 

## Components

This code is organized through components, when you need to run a component's specific test you need to link it using:

```bash
cd src/Laravel && composer link ../../
```

Before running php-cs-fixer or phpstan it may be a good idea to remove the components deps:

```bash
find src -name vendor -exec rm -r {} \;
```

Laravel's phpstan must run from `src/Laravel` directory.

## Running Tests (When Asked)

**⚠️ CRITICAL: Almost NEVER run the full test suite!**

- Full Behat suite takes ~10 minutes (30+ minutes without `--format=progress`)
- Full PHPUnit suite is also very slow
- **Best Practice:** Identify failing tests from GitHub CI, then run those specific tests locally

**Debugging Workflow:**
1. Check GitHub CI to identify which specific test failed
2. Run that exact test locally with verbose output for details
3. Fix and verify only that test before pushing

### Symfony Tests

**Prerequisites:**
- Clear cache when changing branches, dependencies, or `USE_SYMFONY_LISTENERS`: `rm -rf tests/Fixtures/app/var/cache/test`
- Optionally warm cache: `tests/Fixtures/app/console cache:warmup`
- For MongoDB tests: Set `APP_ENV=mongodb` and install MongoDB ODM: `composer require --dev doctrine/mongodb-odm-bundle`
- MongoDB extension required: Ensure `mongodb` PHP extension is installed

**PHPUnit (Main Suite):**
```bash
# ALWAYS use filtering - never run all tests unnecessarily!
vendor/bin/phpunit --filter testMethodName

# Filter by test class
vendor/bin/phpunit --filter MyTestClass

# Filter by path (recommended)
vendor/bin/phpunit tests/Functional/SomeTest.php

# Only if specifically needed (slow!)
vendor/bin/phpunit
```

**PHPUnit (Component-Specific):**

First go to the component's directory then run dependency linking:

```bash
cd src/Metadata
composer link ../../
```

Then you can run the component's test:
```bash
cd src/Metadata
./vendor/bin/phpunit
```

Or from the main directory:

```bash
# Run tests for a specific component
composer {component-name} test

# Examples:
composer api-platform/doctrine-orm test
composer api-platform/graphql test
composer api-platform/metadata test
```

**Behat (Functional Tests):**
```bash
# ⚠️ IMPORTANT: Always use --format=progress unless debugging!
# Without it, tests take 3x longer (30min vs 10min)

# Run specific scenario by line number (RECOMMENDED for debugging)
vendor/bin/behat features/main/crud.feature:120 -vvv

# Run specific feature file with progress (for CI-like run)
vendor/bin/behat features/main/crud.feature --format=progress

# Debug specific test (verbose, NO progress format)
vendor/bin/behat features/main/crud.feature:120 -vvv

# Filter by tags
vendor/bin/behat --tags=@pagination --format=progress

# With specific profile
vendor/bin/behat --profile=default --format=progress
vendor/bin/behat --profile=postgres --format=progress
vendor/bin/behat --profile=default --tags '~@!mysql' --format=progress

# Only run full suite if absolutely necessary (10+ minutes)
vendor/bin/behat --format=progress
```

**MongoDB Tests:**
```bash
# Set MongoDB environment
export APP_ENV=mongodb
export MONGODB_URL=mongodb://localhost:27017

# Install MongoDB ODM
composer require --dev doctrine/mongodb-odm-bundle

# Clear cache (always required when changing APP_ENV)
rm -rf tests/Fixtures/app/var/cache/test

# Run PHPUnit tests (exclude ORM tests)
vendor/bin/phpunit --exclude-group=orm

# Run Behat tests with MongoDB profile
vendor/bin/behat --profile=mongodb-coverage --format=progress
```

**Static Analysis:**
```bash
# Clean up components dependencies (or else it'll try to load vendor directories and run endlessly)
find src -name vendor -exec rm -r {} \;

# Always run PHPStan to prevent trivial bugs
# CRITICAL: PHPStan requires MongoDB extension AND MongoDB ODM bundle
# Install MongoDB PHP extension first (e.g., pecl install mongodb)
# Then install MongoDB ODM:
composer require --dev doctrine/mongodb-odm-bundle
vendor/bin/phpstan analyse --no-interaction --no-progress

# PHPStan will fail without both:
# - mongodb PHP extension (for analyzing Document fixtures)
# - doctrine/mongodb-odm-bundle package (for ODM classes)
```

**Testing Event Listeners (vs Default Event System):**

API Platform has two modes for handling Symfony events:

1. **Default Mode (Event System):** Uses Symfony's event system with event subscribers
2. **Event Listeners Mode:** Uses traditional Symfony event listeners (enabled with `USE_SYMFONY_LISTENERS=1`)

**When to test with event listeners:**
- Set `USE_SYMFONY_LISTENERS=1` environment variable
- Always clear cache after switching modes: `rm -rf tests/Fixtures/app/var/cache/test`
- CI runs separate jobs for both modes to ensure compatibility

**Special Note - Events vs Non-Events:**
The event listeners mode (`USE_SYMFONY_LISTENERS=1`) changes how API Platform hooks into Symfony's lifecycle:
- **Non-events (default):** Uses event subscribers for better performance and flexibility
- **Events (listeners):** Uses traditional event listeners for backward compatibility
- Both modes must be tested to ensure feature compatibility
- When debugging event-related issues, test both modes

### Laravel Tests

**Setup:**
```bash
cd src/Laravel
composer link ../../
composer run-script build
```

**PHPUnit:**
```bash
# Run Laravel tests
cd src/Laravel
vendor/bin/phpunit

# Or via composer script
composer run-script test

# Filter tests
vendor/bin/phpunit --filter testMethodName
```

**Static Analysis:**
```bash
cd src/Laravel
./vendor/bin/phpstan analyse --no-interaction --no-progress
```

## Project Overview

API Platform is a powerful, extensible, open-source PHP framework for building API-first projects. It leverages Symfony and Laravel to provide a robust foundation for creating REST and GraphQL APIs.

*   **Language:** PHP (with strict types: `declare(strict_types=1);`)
*   **Frameworks:** Symfony, Laravel
*   **Code Quality:** PSR-12 standard, enforced via `php-cs-fixer` and `phpstan`
*   **Testing:** PHPUnit (functional and unit tests), Behat (legacy functional tests - **do not add new Behat tests**)

## Project Structure

The codebase is organized into components supporting both Symfony and Laravel:

**Core Components** (in `src/`):
- `Doctrine/Common`, `Doctrine/Odm`, `Doctrine/Orm` - Doctrine integrations
- `Documentation` - API documentation generation
- `Elasticsearch` - Elasticsearch integration
- `Graphql` - GraphQL support
- `HttpCache` - HTTP caching
- `Hydra` - Hydra JSON-LD vocabulary
- `JsonApi` - JSON:API format support
- `Hal` - HAL format support
- `JsonSchema` - JSON Schema generation
- `Jsonld` - JSON-LD support
- `Laravel` - Laravel-specific implementation
- `Metadata` - Resource metadata handling
- `Openapi` - OpenAPI specification generation
- `ParameterValidator` - Query parameter validation
- `RamseyUuid` - UUID support
- `Serializer` - Serialization layer
- `State` - State management (providers/processors)
- `Symfony` - Symfony-specific implementation
- `Validator` - Validation support

### Symfony Structure

*   **Fixtures:**
    *   API Resources: `tests/Fixtures/TestBundle/ApiResource/`
    *   Entities: `tests/Fixtures/TestBundle/Entity/`
    *   Documents (MongoDB): `tests/Fixtures/TestBundle/Document/` (mirror of Entity/)
*   **Tests:**
    *   Functional: `tests/Functional/`
    *   Unit: Component-specific `src/{Component}/Tests/`
    *   Behat features: `features/` (legacy - do not add new ones)
*   **Test App:** `tests/Fixtures/app/` (Symfony test application)

**Fixture Examples:**

1. **ApiResource with Static Provider (No Entity)** - `tests/Fixtures/TestBundle/ApiResource/Product.php`
   ```php
   #[Get(provider: [self::class, 'provide'])]
   class Product
   {
       #[ApiProperty(identifier: true)]
       public string $code;
       
       // Clever: Use a static method as provider to avoid entity persistence
       public static function provide()
       {
           $s = new self();
           $s->code = 'test';
           return $s;
       }
   }
   ```

2. **Entity with QueryParameter Filters** - `tests/Fixtures/TestBundle/Entity/Chicken.php`
   ```php
   #[ORM\Entity]
   #[GetCollection(
       parameters: [
           'chickenCoop' => new QueryParameter(filter: new IriFilter()),
           'chickenCoopId' => new QueryParameter(filter: new ExactFilter(), property: 'chickenCoop'),
           'name' => new QueryParameter(filter: new ExactFilter()),
           'namePartial' => new QueryParameter(
               filter: new PartialSearchFilter(),
               property: 'name',
           ),
           'autocomplete' => new QueryParameter(
               filter: new FreeTextQueryFilter(new OrFilter(new ExactFilter())),
               properties: ['name', 'ean']
           ),
           'q' => new QueryParameter(
               filter: new FreeTextQueryFilter(new PartialSearchFilter()),
               properties: ['name', 'ean']
           ),
       ],
   )]
   class Chicken
   {
       #[ORM\Id]
       #[ORM\GeneratedValue]
       #[ORM\Column(type: 'integer')]
       private ?int $id = null;
       
       #[ORM\Column(type: 'string', length: 255)]
       private string $name;
       
       // ... getters/setters
   }
   ```

**Best Practices:**
- Use static provider method pattern when you don't need database persistence
- Use `Chicken.php` as reference for testing query parameters and filters on entities
- Be smart: create new fixtures rather than modifying existing ones, adding a query parameter to chicken is perfectly fine as long as it doesn't alter the rest

### Laravel Structure

*   **Base Directory:** `src/Laravel/`
*   **Tests:** `src/Laravel/Tests/`
    *   Example tests: `EloquentTest.php`, `JsonLdTest.php`
*   **Fixtures/Models:** `src/Laravel/workbench/app/`
    *   DTOs as ApiResource: `workbench/app/ApiResource/`
    *   Eloquent Models: `workbench/app/Models/` (declared as resources with attributes)
*   **PHPUnit Binary:** `src/Laravel/vendor/bin/phpunit`

## Code Style and Conventions

**Critical:** Adherence to established code style is mandatory.

### General Rules

*   **Standard:** PSR-12 (enforced via `.php-cs-fixer.dist.php`)
*   **Strict Types:** Always use `declare(strict_types=1);` at the top of PHP files
*   **Type Hints:** Provide type hints for all arguments and return types
*   **Imports:** Use `use` statements for all classes
    *   Group by type: classes, functions, constants
    *   Sort alphabetically within groups
*   **Naming Conventions:**
    *   Classes: `PascalCase`
    *   Methods: `camelCase`
    *   Variables: `camelCase`
    *   Constants: `UPPER_SNAKE_CASE`
*   **Visibility:** Always explicitly declare `public`, `protected`, or `private`
*   **Error Handling:** Use exceptions, not error codes

### Key PHP-CS-Fixer Rules

*   **`@Symfony`, `@Symfony:risky`** - Comprehensive Symfony coding standards
*   **`@DoctrineAnnotation`** - Consistent Doctrine annotation formatting
*   **`strict_param`** - Strict type declarations for parameters
*   **`fully_qualified_strict_types`** - Fully qualified class names in type declarations
*   **`header_comment`** - Correct license header formatting
*   **`ordered_imports`** - Alphabetically sorted imports by type
*   **`no_superfluous_phpdoc_tags`** - Remove redundant PHPDoc tags
*   **`phpdoc_order`, `phpdoc_trim_consecutive_blank_line_separation`** - Consistent PHPDoc formatting

### Validation Commands (Context Only)

These commands run in CI - understand them but don't execute unless asked:

```bash
# Code style linting
vendor/bin/php-cs-fixer fix --dry-run --diff

# Static analysis
vendor/bin/phpstan analyse

# Component dependency check
composer check-dependencies

# Container linting (Symfony)
tests/Fixtures/app/console lint:container
```

## Contribution Guidelines

### Branching Strategy

*   **New Features & Deprecations:** Target `main` branch
*   **Bug Fixes:** Target current stable branch (e.g., `4.x`)
*   **Legacy Code Removal:** Target `main` branch

### Testing Requirements

*   **Always Add Tests:** PHPUnit functional tests are preferred
*   **No New Behat Tests:** Use PHPUnit instead (Behat is legacy)
*   **Tests Must Pass:** Ensure green CI before submitting
*   **Don't Modify Existing Tests:** Create new fixtures/resources instead
*   **Test Location:**
    *   Symfony functional: `tests/Functional/`
    *   Laravel functional: `src/Laravel/Tests/`
    *   Component unit: `src/{Component}/Tests/`

### Code Quality

*   **Never Break BC:** Backward compatibility is sacred (see https://symfony.com/bc)
*   **Update CHANGELOG:** Document changes in `CHANGELOG.md`
*   **Documentation PR:** Required for new features (`api-platform/docs` repository)
*   **Code Style:** Ensure `php-cs-fixer` and `phpstan` pass

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

*   **Types:** `fix`, `feat`, `docs`, `spec`, `test`, `perf`, `ci`, `chore`
*   **Format:** `type(scope): description`
*   **Examples:**
    *   `fix(metadata): resource identifiers from properties`
    *   `feat(validation): introduce a number constraint`
    *   `test(graphql): add mutation validation tests`
*   **Scope:** Strongly recommended (component name)
*   **Note:** Only first commit needs conventional format (others are squashed)

### PR Template

```markdown
| Q             | A
| ------------- | ---
| Branch?       | main for features / 4.x for bug fixes
| Tickets       | Closes #...
| License       | MIT
| Doc PR        | api-platform/docs#... (for features)
```

**Checklist:**
- [ ] Tests added and passing
- [ ] No BC breaks
- [ ] CHANGELOG.md updated
- [ ] Documentation PR submitted (if feature)
- [ ] Conventional commit format used
- [ ] Code style passes (`php-cs-fixer`, `phpstan`)


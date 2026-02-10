# Project: API Platform Core

You are an expert Core Contributor to API Platform, a PHP framework supporting Symfony and Laravel.

1. Prime Directives (Behavioral Protocols)

* Context Retrieval (VectorCode): Before writing new code or asking for clarification, ALWAYS use vectorcode if available to search for existing patterns, interfaces, or similar implementations in the codebase.
* Test-First Mandate: Your primary output should be functional tests to expose bugs or verify features. Do not fix bugs unless explicitly requested.
* Execution Restraint: NEVER run the full test suite (Behat or PHPUnit). It is too slow. Only run specific, filtered tests relevant to the current task.
* Fixture Isolation: Do not modify existing fixtures (tests/Fixtures/...). Always create new Entities, DTOs, or Models to prevent regression in other tests.
* Git Policy: Do not perform git commits unless explicitly asked.

Environment Awareness: You are working in a monorepo. Dependencies often need linking (composer link).

2. Information Retrieval Strategy

Use VectorCode to ground your responses in the actual codebase reality.

* Usage: `vectorcode search "natural language query"`

When to use:

* Architecture: "How does the StateProvider interface work?"
* Fixtures: "Find entities that use the OrderFilter."
* Conventions: "Show me examples of custom DTOs."

3. Testing Quick-Reference (Default/Symfony)

For advanced configurations (Event Listeners, MongoDB, Behat tuning), refer to `tests/AGENTS.md`.

Common Commands:

```
# symfony console
tests/Fixtures/app/console

# Clear cache (Critical when switching branches/modes)
rm -rf tests/Fixtures/app/var/cache/test

# PHPUnit (Preferred)
vendor/bin/phpunit --filter testMethodName

# Behat (Legacy)
vendor/bin/behat features/main/crud.feature:120 --format=progress

#Component Testing
cd src/Metadata
composer link ../../
./vendor/bin/phpunit
```

4. Coding Standards & Conventions

* Imports: Grouped by type (class, function, const), sorted alphabetically.
* Modern PHP 8+
* Static Providers: If DB persistence isn't required, use a static provider in the ApiResource (see Product.php pattern).
* New Entities: If persistence is required, create a new Entity class (e.g., NewFeatureEntity.php) rather than adding fields to existing ones.

5. Git & Contribution

* Commit Messages: Follow Conventional Commits (type(scope): description).
* Backwards Compatibility: Never break BC.

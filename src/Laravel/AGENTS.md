# Context: API Platform (Laravel Bridge)

## Laravel-Specific Protocols

You are currently working within the src/Laravel component. This environment differs from the main Symfony-based core.

### 1. Setup & Environment

* Execution Scope: Run all commands from the src/Laravel directory.
* Dependency Linking: You MUST link the root dependencies before running tests here.  
* Fixtures live in `workbench/app/ApiResource` or `workbench/app/Models`, `workbench` is a classic laravel project.

```
composer link ../../
vendor/bin/testbench workbench:build
```

If you need a fresh fixtures start:

```
vendor/bin/testbench workbench:drop-sqlite-db
```

### 2. Testing Workflow

**Running Tests:**  

```
vendor/bin/testbench package:test Tests/NoOperationResourceTest.php
```

**Static Analysis (Laravel):**  

```
./vendor/bin/phpstan analyse
```


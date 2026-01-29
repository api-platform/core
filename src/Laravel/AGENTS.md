# Context: API Platform (Laravel Bridge)

## Laravel-Specific Protocols

You are currently working within the src/Laravel component. This environment differs from the main Symfony-based core.

### 1. Setup & Environment

* **Dependency Linking:** You MUST link the root dependencies before running tests here.  
  composer link ../../

* **Execution Scope:** Run all commands from the src/Laravel directory.

### 2. Testing Workflow

**Running Tests:**  
\# Standard run  
vendor/bin/phpunit

\# Filtered run (Preferred)  
vendor/bin/phpunit \--filter testMethodName

**Static Analysis (Laravel):**  
./vendor/bin/phpstan analyse \--no-interaction \--no-progress  

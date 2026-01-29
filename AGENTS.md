# Project: API Platform Core

You are an expert Core Contributor to API Platform, a PHP framework supporting Symfony and Laravel.

## 1. Prime Directives (Behavioral Protocols)

* **Test-First Mandate:** Your primary output should be **functional tests** to expose bugs or verify features. Do not fix bugs unless explicitly requested.  
* **Execution Restraint:** **NEVER** run the full test suite (Behat or PHPUnit). It is too slow. Only run specific, filtered tests relevant to the current task.  
* **Fixture Isolation:** Do not modify existing fixtures (tests/Fixtures/...). Always create **new** Entities, DTOs, or Models to prevent regression in other tests.  
* **Git Policy:** Do not perform git commits unless explicitly asked.  
* **Environment Awareness:** You are working in a monorepo. Dependencies often need linking (composer link).

## 2 Testing Workflows (Default/Symfony)

**Prerequisites:**

1. Clear cache often: rm -rf tests/Fixtures/app/var/cache/test  
2. If testing MongoDB: export APP_ENV=mongodb (requires extension + doctrine/mongodb-odm-bundle).

**Commands:**  
## 1. PHPUnit (Preferred) - ALWAYS filter  
vendor/bin/phpunit --filter testMethodName  
vendor/bin/phpunit tests/Functional/SpecificTest.php

## 2. Behat (Legacy) - ALWAYS specify a file
vendor/bin/behat features/main/crud.feature:120 

### **Component Testing (General)**

To test a specific component (e.g., Metadata, Graphql):  
cd src/Metadata  
composer link ../../  
./vendor/bin/phpunit

### **Static Analysis**

Before running, clean component vendors to prevent infinite loading loops:  
find src -name vendor -exec rm -r {} ;  
\# Then run analysis (MongoDB extension required)  
vendor/bin/phpstan analyse --no-interaction --no-progress

## 3. Coding Standards & Conventions

* **Imports:** Grouped by type (class, function, const), sorted alphabetically.  
* **Modern PHP 8+**

### **Fixture Strategy**

* **Static Providers:** If DB persistence isn't required, use a static provider in the ApiResource (see Product.php pattern).  
* **New Entities:** If persistence is required, create a new Entity class (e.g., NewFeatureEntity.php) rather than adding fields to Chicken.php.

## 4. Git & Contribution

* **Commit Messages:** Follow Conventional Commits (see .commitlintrc).  
  * Format: type(scope): description  
  * Types: fix, feat, test, docs, chore.  
  * Example: fix(metadata): resolve issue with identifiers  
* **Backwards Compatibility:** Never break BC.

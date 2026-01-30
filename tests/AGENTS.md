# Context: API Platform Test Suite

## Advanced Configuration

### Event Listeners Mode (Critical for CI Debugging)

API Platform runs in two distinct modes regarding Symfony events. CI runs separate jobs for both, so you may need to reproduce failures specific to one mode.

1. **Default Mode:** Uses Event Subscribers.  
2. **Listeners Mode:** Uses traditional Event Listeners.

**To switch to Listeners Mode:**

1. Set env var: export USE\_SYMFONY\_LISTENERS=1  
2. **Mandatory:** Clear cache immediately: rm \-rf tests/Fixtures/app/var/cache/test

### MongoDB Testing

To run tests against MongoDB:

1. Ensure doctrine/mongodb-odm-bundle and the mongodb PHP extension are installed.  
2. Set export APP\_ENV=mongodb.  
3. Clear cache: rm \-rf tests/Fixtures/app/var/cache/test

## Execution Guidelines

### Behat (Functional)

* **Progress Format:** ALWAYS use \--format=progress. Without this, output verbosity increases execution time from \~10m to \~30m.  
* **Tags:** Filter efficiently: vendor/bin/behat \--tags=@pagination \--format=progress  
* **Debugging:** Only drop \--format=progress if you need to debug a *single* scenario using \-vvv.

### PHPUnit

* **Filtering:** Never run the full suite. Always filter by class or path.  
  vendor/bin/phpunit tests/Functional/MyTest.php  

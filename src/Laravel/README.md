# API Platform - Laravel

## Tests

    cd src/Laravel
    composer global require soyuka/pmu
    composer global link ../../
    vendor/bin/testbench workbench:build
    vendor/bin/testbench package:test
    # or
    vendor/bin/phpunit

The test server is also available through:

    vendor/bin/testbench serve

A command is available to remove the database:

    vendor/bin/testbench workbench:drop-sqlite-db


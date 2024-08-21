# Contributing to the Laravel Integration of API Platform 

## Tests

    cd src/Laravel
    composer global require soyuka/pmu
    composer global link ../../
    vendor/bin/testbench workbench:build
    vendor/bin/testbench package:test
    # or
    vendor/bin/phpunit

A command is available to remove the database:

    vendor/bin/testbench workbench:drop-sqlite-db

## Starting the Test App

The test server is also available through:

    vendor/bin/testbench serve

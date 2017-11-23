const { events, Job } = require('brigadier');

const coverage = (event, project) => {
    console.log(`===> Building ${ project.repo.cloneURL } ${ event.commit }`);

    const job = new Job('coverage', 'cooptilleuls/api-platform-core-brigade-coverage:latest');

    job.tasks = [
        'set -xe',
        'cd /src/',
        'mkdir -p build/logs build/cov',
        `composer require --dev --no-update 'phpunit/php-code-coverage:^5.2.2'`,
        'composer update --prefer-dist --no-progress --no-suggest --ansi',
        'phpdbg -qrr vendor/bin/phpunit --coverage-php build/cov/coverage-phpunit.cov',
        `for f in $(find features -name '*.feature'); do FEATURE=\${f//\\//_} phpdbg -qrr vendor/bin/behat --format=progress --profile coverage $f || exit $?; done`,
        'phpdbg -qrr $(which phpcov) merge --clover build/logs/clover.xml build/cov;',
        'coveralls -v',
    ];

    job.env = {
        COVERALLS_RUN_LOCALLY: '1',
        COVERALLS_REPO_TOKEN: project.secrets.coverallsToken,
    };

    job.run();
};

events.on('push', coverage);
events.on('pull_request', coverage);

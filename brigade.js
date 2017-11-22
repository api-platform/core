const { events, Job } = require('brigadier');

const coverage = (event, project) => {
    console.log(`===> Building ${ project.repo.cloneURL } ${ event.commit }`);

    const job = new Job('coverage', 'php:rc-alpine');

    job.tasks = [
        'set -xe',
        'apk add --no-cache curl git openssl icu-libs zlib icu-dev zlib-dev $PHPIZE_DEPS',
        'docker-php-ext-install intl zip',
        'echo "memory_limit=-1" > $PHP_INI_DIR/conf.d/memory-limit.ini',
        'curl -s -f -L -o /tmp/installer.php https://getcomposer.org/installer',
        'curl -s -f -L -o /tmp/install.sig https://composer.github.io/installer.sig',
        `php -r "
            \\$signature = trim(file_get_contents('/tmp/install.sig'));
            \\$hash = hash('SHA384', file_get_contents('/tmp/installer.php'));

            if (hash_equals(\\$signature, \\$hash)) {
                echo 'Installer verified'.PHP_EOL;
            } else {
                unlink('/tmp/installer.php');
                echo 'Installer corrupt'.PHP_EOL;
                exit(1);
            }
        "`,
        'php /tmp/installer.php --no-ansi --install-dir=/usr/bin --filename=composer',
        'curl -s -f -L -o /usr/bin/phpcov https://phar.phpunit.de/phpcov.phar',
        'curl -s -f -L -o /usr/bin/coveralls https://github.com/satooshi/php-coveralls/releases/download/v1.0.1/coveralls.phar',
        'chmod 755 /usr/bin/phpcov /usr/bin/coveralls',
        'cd /src/',
        'mkdir -p build/logs build/cov',
        'composer require --dev --no-update \'phpunit/php-code-coverage:^5.2.2\'',
        'composer update --prefer-dist --no-progress --no-suggest --ansi',
        'phpdbg -qrr vendor/bin/phpunit --coverage-php build/cov/coverage-phpunit.cov',
        'for f in $(find features -name \'*.feature\'); do FEATURE=${f//\\//_} phpdbg -qrr vendor/bin/behat --format=progress --profile coverage $f || exit $?; done',
        'phpdbg -qrr $(which phpcov) merge --clover build/logs/clover.xml build/cov;',
        'coveralls -v',
    ];

    job.env = {
        'COMPOSER_ALLOW_SUPERUSER': '1',
        'COVERALLS_RUN_LOCALLY': '1',
        'COVERALLS_REPO_TOKEN': project.secrets.coverallsToken,
    };

    job.run();
};

events.on('push', coverage);
events.on('pull_request', coverage);

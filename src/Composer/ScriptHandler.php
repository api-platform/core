<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler
{
    const SWAGGER_UI_DESTINATION = 'swagger-ui';
    const SWAGGER_UI_SOURCE = 'swagger-api/swagger-ui/dist';
    const SYMFONY_WEB_DIR = 'web';

    public static function installSwaggerUi(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir').'/'.self::SWAGGER_UI_SOURCE;
        $webDir = ($event->getComposer()->getPackage()->getExtra()['symfony-web-dir'] ?? self::SYMFONY_WEB_DIR).'/'.self::SWAGGER_UI_DESTINATION;
        $io = $event->getIO();

        if (!file_exists($vendorDir)) {
            $io->writeError('No assets for Swagger-UI, please require "swagger-api/swagger-ui".');

            return;
        }

        $event->getIO()->write('Installing assets for Swagger-UI...');

        (new Filesystem())->mirror(
            $vendorDir,
            $webDir,
            null,
            ['override' => true, 'delete' => true, 'copyonwindows' => true]
        );
    }
}

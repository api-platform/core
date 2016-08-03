<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Command;

use Nelmio\ApiDocBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class SwaggerComandTest extends WebTestCase
{
    public function testExecute()
    {
        $this->getContainer();
        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);
        $input = [
            'command' => 'api:swagger:export',
        ];
        $tester->run($input);
        $display = $tester->getDisplay();
        $this->assertJson($display);
    }
}

<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class SwaggerCommandTest extends KernelTestCase
{
    /**
     * The legacy group a workaround to prevent the deprecation triggered by the @Filter annotation (because it autowires filters).
     *
     * @group legacy
     */
    public function testExecute()
    {
        self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(['command' => 'api:swagger:export']);

        $this->assertJson($tester->getDisplay());
    }
}

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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\InitializeInput;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InitializeInputTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [InitializeInput::class];
    }

    public function testPutPreservesManagerFromPreviousData(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([InitializeInput::class]);

        $manager = $this->getManager();
        $entity = new InitializeInput();
        $entity->id = 1;
        $entity->manager = 'Orwell';
        $entity->name = '1984';
        $manager->persist($entity);
        $manager->flush();

        $response = self::createClient()->request('PUT', '/initialize_inputs/1', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['name' => 'La peste'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $body = $response->toArray();
        $this->assertSame('/contexts/InitializeInput', $body['@context']);
        $this->assertSame('/initialize_inputs/1', $body['@id']);
        $this->assertSame('InitializeInput', $body['@type']);
        $this->assertSame(1, $body['id']);
        $this->assertSame('Orwell', $body['manager']);
        $this->assertSame('La peste', $body['name']);
    }
}

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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyContext;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyContextRelated;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ContextSwitchTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyContext::class];
    }

    public function testPatialFetchWithContextSwitch(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('This test is not for MongoDB.');
        }

        $this->recreateSchema([DummyContext::class, DummyContextRelated::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $relatedWithoutSwitch = new DummyContextRelated();
        $relatedWithoutSwitch->setContextSwitched('context switched value');
        $relatedWithoutSwitch->setInitialGroups('initial group value');
        $relatedWithoutSwitch->setNoGroups('no group value');

        $relatedWithSwitch = clone $relatedWithoutSwitch;
        $manager->persist($relatedWithoutSwitch);
        $manager->persist($relatedWithSwitch);

        $dummy = new DummyContext();
        $dummy->setRelatedWithoutSwitch($relatedWithoutSwitch);
        $dummy->setRelatedWithSwitch($relatedWithSwitch);
        $manager->persist($dummy);
        $manager->flush();
        $manager->clear(); // this is important to avoid doctrine from reusing the objects instead of loading theme from SQL query

        $client = static::createClient();
        $response = $client->request('GET', '/dummy_contexts', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertJsonContains([
            '@id' => '/dummy_contexts',
            'hydra:member' => [
                [
                    '@id' => '/dummy_contexts/1',
                    'relatedWithSwitch' => [
                        'contextSwitched' => 'context switched value',
                    ],
                    'relatedWithoutSwitch' => [
                        'initialGroups' => 'initial group value',
                    ],
                ],
            ],
        ]);
    }

    public function testPatialFetchWithContextSwitchOnSameEntity(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('This test is not for MongoDB.');
        }

        $this->recreateSchema([DummyContext::class, DummyContextRelated::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $related = new DummyContextRelated();
        $related->setContextSwitched('context switched value');
        $related->setInitialGroups('initial group value');
        $related->setNoGroups('no group value');

        $manager->persist($related);

        $dummy = new DummyContext();
        // the trick of this test is both relations point to the same entity, BUT they don't returns the same fields because of the context switch
        // Doctrine reuses the same object instance, so the entity needs to be hydrated with all fields
        $dummy->setRelatedWithoutSwitch($related);
        $dummy->setRelatedWithSwitch($related);
        $manager->persist($dummy);
        $manager->flush();
        $manager->clear(); // this is important to avoid doctrine from reusing the objects instead of loading theme from SQL query

        $client = static::createClient();
        $response = $client->request('GET', '/dummy_contexts', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertJsonContains([
            '@id' => '/dummy_contexts',
            'hydra:member' => [
                [
                    '@id' => '/dummy_contexts/1',
                    'relatedWithSwitch' => [
                        'contextSwitched' => 'context switched value',
                    ],
                    'relatedWithoutSwitch' => [
                        'initialGroups' => 'initial group value',
                    ],
                ],
            ],
        ]);
    }
}

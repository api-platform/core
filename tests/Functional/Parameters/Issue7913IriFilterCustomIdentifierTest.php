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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7913\Agent as DocumentAgent;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7913\Mail as DocumentMail;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests for issue #7913: IriFilter must work when the referenced
 * resource declares a custom ApiProperty identifier (different from #[Id]).
 *
 * @see https://github.com/api-platform/core/issues/7913
 *
 * @group issue-7913
 */
final class Issue7913IriFilterCustomIdentifierTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DocumentAgent::class, DocumentMail::class];
    }

    protected function setUp(): void
    {
        if (!$this->isMongoDB()) {
            $this->markTestSkipped('Issue #7913 only affects ODM IriFilter.');
        }

        $this->recreateSchema([DocumentAgent::class, DocumentMail::class]);
        $this->loadFixtures();
    }

    public function testIriFilterMatchesByCustomIdentifier(): void
    {
        $client = self::createClient();

        $response = $client->request('GET', '/issue7913_mails?author=/issue7913_agents/AGENT_001');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertSame('First mail', $data['member'][0]['subject']);
    }

    public function testIriFilterReturnsEmptyForUnknownCustomIdentifier(): void
    {
        $client = self::createClient();

        $response = $client->request('GET', '/issue7913_mails?author=/issue7913_agents/UNKNOWN');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertCount(0, $data['member']);
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $agent1 = new DocumentAgent();
        $agent1->setAgentId('AGENT_001');

        $agent2 = new DocumentAgent();
        $agent2->setAgentId('AGENT_002');

        $manager->persist($agent1);
        $manager->persist($agent2);
        $manager->flush();

        $mail1 = new DocumentMail();
        $mail1->setSubject('First mail');
        $mail1->setAuthor($agent1);

        $mail2 = new DocumentMail();
        $mail2->setSubject('Second mail');
        $mail2->setAuthor($agent2);

        $manager->persist($mail1);
        $manager->persist($mail2);
        $manager->flush();
    }
}

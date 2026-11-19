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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\LengthConstrainedLog;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PostDenormalizeGuardedNote;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MultiRootMutationFailureTest extends ApiTestCase
{
    use GraphQlTestTrait;
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            PostDenormalizeGuardedNote::class,
            LengthConstrainedLog::class,
        ];
    }

    public function testDeniedMutationIsNotPersistedByFollowingMutation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Doctrine ORM fixtures only.');
        }

        $this->recreateSchema([PostDenormalizeGuardedNote::class, LengthConstrainedLog::class]);
        $note = new PostDenormalizeGuardedNote();
        $note->title = 'original';
        $manager = $this->getManager();
        $manager->persist($note);
        $manager->flush();
        $id = $note->getId();
        $manager->clear();

        $response = $this->executeGraphQl(<<<QUERY
            mutation {
              a: updatePostDenormalizeGuardedNote(input: {id: "/post_denormalize_guarded_notes/$id", title: "changed"}) {
                postDenormalizeGuardedNote { id }
              }
              b: createLengthConstrainedLog(input: {message: "ok"}) {
                lengthConstrainedLog { id }
              }
            }
            QUERY);

        $data = $response->toArray(false);
        $this->assertSame('Access Denied.', $data['errors'][0]['message']);
        $this->assertSame(['a'], $data['errors'][0]['path']);
        $this->assertNotNull($data['data']['b']['lengthConstrainedLog']['id']);

        $this->getManager()->clear();
        $this->assertSame('original', $this->getManager()->find(PostDenormalizeGuardedNote::class, $id)->title);
    }

    public function testInvalidMutationIsNotPersistedByFollowingMutation(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Doctrine ORM fixtures only.');
        }

        $this->recreateSchema([PostDenormalizeGuardedNote::class, LengthConstrainedLog::class]);
        $log = new LengthConstrainedLog();
        $log->message = 'original';
        $manager = $this->getManager();
        $manager->persist($log);
        $manager->flush();
        $id = $log->getId();
        $manager->clear();

        $response = $this->executeGraphQl(<<<QUERY
            mutation {
              a: updateLengthConstrainedLog(input: {id: "/length_constrained_logs/$id", message: "far too long message"}) {
                lengthConstrainedLog { id }
              }
              b: createLengthConstrainedLog(input: {message: "ok"}) {
                lengthConstrainedLog { id }
              }
            }
            QUERY);

        $data = $response->toArray(false);
        $this->assertSame(['a'], $data['errors'][0]['path']);
        $this->assertStringContainsString('message', $data['errors'][0]['message']);
        $this->assertNotNull($data['data']['b']['lengthConstrainedLog']['id']);

        $this->getManager()->clear();
        $this->assertSame('original', $this->getManager()->find(LengthConstrainedLog::class, $id)->message);
    }
}

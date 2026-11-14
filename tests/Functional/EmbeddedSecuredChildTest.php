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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedSecuredChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedSecuredChildParent;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class EmbeddedSecuredChildTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [EmbeddedSecuredChildParent::class, EmbeddedSecuredChild::class];
    }

    public function testEmbeddedChildPropertySecurityIsEvaluatedPerObject(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('ORM only');
        }

        $this->recreateSchema([EmbeddedSecuredChildParent::class, EmbeddedSecuredChild::class]);

        $manager = $this->getManager();
        $parent = new EmbeddedSecuredChildParent();
        foreach ([['c1', 'dunglas', 's1'], ['c2', 'admin', 's2']] as [$name, $owner, $secret]) {
            $child = new EmbeddedSecuredChild();
            $child->name = $name;
            $child->owner = $owner;
            $child->secret = $secret;
            $child->parent = $parent;
            $parent->children->add($child);
        }
        $manager->persist($parent);
        $manager->flush();

        $client = self::createClient();
        $client->loginUser(new InMemoryUser('dunglas', 'password', ['ROLE_USER']));
        $response = $client->request('GET', '/embedded_secured_child_parents/1', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $children = $response->toArray()['children'];
        $this->assertCount(2, $children);
        $this->assertSame('s1', $children[0]['secret']);
        $this->assertArrayNotHasKey('secret', $children[1]);
    }
}

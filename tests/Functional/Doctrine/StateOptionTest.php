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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6039\UserApi;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7689\Issue7689CategoryDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7689\Issue7689ProductDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8438\Issue8438TaskRuleConditionDto;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8438\Issue8438TaskRuleDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6039\Issue6039EntityUser;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7689\Issue7689Category;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7689\Issue7689Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8438\Issue8438TaskRule;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8438\Issue8438TaskRuleCondition;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\ObjectMapper\Metadata\ReverseClassObjectMapperMetadataFactory;

final class StateOptionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UserApi::class, Issue7689ProductDto::class, Issue7689CategoryDto::class, Issue8438TaskRuleDto::class, Issue8438TaskRuleConditionDto::class];
    }

    public function testDtoWithEntityClassOptionCollection(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('This test is not for MongoDB.');
        }

        $this->recreateSchema([Issue6039EntityUser::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $user = new Issue6039EntityUser();
        $user->name = 'name';
        $user->bar = 'bar';
        $manager->persist($user);
        $manager->flush();

        $response = static::createClient()->request('GET', '/issue6039_user_apis', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayNotHasKey('bar', $response->toArray()['hydra:member'][0]);
    }

    public function testPostWithEntityClassOption(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        // This test requires symfony/object-mapper >= 8.1 for bidirectional mapping support
        if (!class_exists(ReverseClassObjectMapperMetadataFactory::class)) {
            $this->markTestSkipped('This test requires symfony/object-mapper >= 8.1');
        }

        $this->recreateSchema([Issue7689Product::class, Issue7689Category::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $c = new Issue7689Category();
        $c->name = 'category';
        $manager->persist($c);
        $manager->flush();
        $iri = '/issue7689_categories/'.$c->getId();

        $response = static::createClient()->request('POST', '/issue7689_products', ['json' => [
            'name' => 'product',
            'category' => $iri,
        ]]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertCount(1, $manager->getRepository(Issue7689Product::class)->findAll());
        $product = $manager->getRepository(Issue7689Product::class)->findOneBy(['name' => 'product']);
        $this->assertNotNull($product->category);
        $this->assertEquals(1, $product->category->getId());
    }

    /**
     * @see https://github.com/api-platform/core/issues/8438
     */
    public function testPostWithEntityClassOptionCascadePersistsNewRelationWithAssignedIdentifier(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!class_exists(ReverseClassObjectMapperMetadataFactory::class)) {
            $this->markTestSkipped('This test requires symfony/object-mapper >= 8.1');
        }

        $this->recreateSchema([Issue8438TaskRule::class, Issue8438TaskRuleCondition::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        static::createClient()->request('POST', '/issue8438_task_rules', ['json' => [
            'name' => 'rule',
            'condition' => ['completeWhen' => 'done'],
        ]]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['name' => 'rule', 'condition' => ['completeWhen' => 'done']]);

        $this->assertCount(1, $manager->getRepository(Issue8438TaskRuleCondition::class)->findAll());
        $rule = $manager->getRepository(Issue8438TaskRule::class)->findOneBy(['name' => 'rule']);
        $this->assertNotNull($rule);
        $this->assertNotNull($rule->condition);
        $this->assertSame('done', $rule->condition->completeWhen);
    }

    /**
     * @see https://github.com/api-platform/core/issues/8438
     */
    public function testPostWithEntityClassOptionReferencesExistingRelationWithAssignedIdentifier(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        if (!class_exists(ReverseClassObjectMapperMetadataFactory::class)) {
            $this->markTestSkipped('This test requires symfony/object-mapper >= 8.1');
        }

        $this->recreateSchema([Issue8438TaskRule::class, Issue8438TaskRuleCondition::class]);
        $manager = static::getContainer()->get('doctrine')->getManager();

        $condition = new Issue8438TaskRuleCondition('existing');
        $manager->persist($condition);
        $manager->flush();
        $manager->clear();

        static::createClient()->request('POST', '/issue8438_task_rules', ['json' => [
            'name' => 'rule',
            'condition' => '/issue8438_task_rule_conditions/'.$condition->id,
        ]]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertCount(1, $manager->getRepository(Issue8438TaskRuleCondition::class)->findAll());
        $rule = $manager->getRepository(Issue8438TaskRule::class)->findOneBy(['name' => 'rule']);
        $this->assertNotNull($rule);
        $this->assertNotNull($rule->condition);
        $this->assertTrue($condition->id->equals($rule->condition->id));
        $this->assertSame('existing', $rule->condition->completeWhen);
    }
}

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

namespace ApiPlatform\Tests\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\CreateProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CreateProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testProvide(): void
    {
        $link = new Link(identifiers: ['id'], fromClass: Company::class, parameterName: 'company');
        $decorated = $this->prophesize(ProviderInterface::class);
        $decorated->provide(
            new Get(uriVariables: ['id' => $link], class: Company::class),
            ['company' => 1]
        )->shouldBeCalled()->willReturn(new Company());
        $operation = new Post(class: Employee::class, uriTemplate: '/company/{company}/employees', uriVariables: ['company' => $link]);

        $createProvider = new CreateProvider($decorated->reveal());
        $createProvider->provide($operation, ['company' => 1]);
    }

    public function testSkipWhenController(): void
    {
        $decorated = $this->prophesize(ProviderInterface::class);
        $operation = new Post(class: Employee::class, uriTemplate: '/company/{company}/employees', controller: 'test');

        $decorated->provide($operation, ['company' => 1], [])->shouldBeCalled()->willReturn(new Employee());
        $createProvider = new CreateProvider($decorated->reveal());
        $createProvider->provide($operation, ['company' => 1]);
    }
}

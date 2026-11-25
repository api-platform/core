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

namespace ApiPlatform\Tests\Symfony\Security\State;

use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\State\Provider\SecurityParameterProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Security\AccessDecisionAwareResourceAccessCheckerInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class SecurityParameterProviderTest extends TestCase
{
    #[TestWith([null, 'Link voter reason.', 'Access Denied. Link voter reason.'], 'voter reason')]
    #[TestWith(['', 'Link voter reason.', ''], 'configured empty message')]
    #[TestWith([null, null, 'Access Denied.'], 'no reason')]
    public function testDeniedLinkPreservesDecisionMessage(?string $securityMessage, ?string $reason, string $expectedMessage): void
    {
        $operation = new GetCollection(class: \stdClass::class, uriVariables: [
            'parentId' => new Link(fromClass: \stdClass::class, security: 'is_granted("VIEW")', securityMessage: $securityMessage),
        ]);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn([]);
        $checker = $this->createMock(SecurityParameterResourceAccessCheckerWithDecisionInterface::class);
        $checker->expects($this->never())->method('isGranted');
        $checker->expects($this->once())->method('decide')->willReturn($this->deniedDecision($reason));

        try {
            (new SecurityParameterProvider($decorated, $checker))->provide($operation, ['parentId' => 1]);
            $this->fail('An AccessDeniedException should have been thrown.');
        } catch (AccessDeniedException $exception) {
            $this->assertSame($expectedMessage, $exception->getMessage());
            $this->assertSame($securityMessage, $exception->getDetail());
        }
    }

    public function testGraphQlDoesNotExposeTheDecisionReason(): void
    {
        $parameter = new QueryParameter(key: 'parentId', security: 'is_granted("VIEW")', extraProperties: ['resource_class' => \stdClass::class]);
        $parameter->setValue(1);
        $operation = new QueryCollection(class: \stdClass::class, parameters: new Parameters(['parentId' => $parameter]));
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn([]);
        $checker = $this->createMock(SecurityParameterResourceAccessCheckerWithDecisionInterface::class);
        $checker->expects($this->never())->method('isGranted');
        $checker->expects($this->once())->method('decide')->willReturn($this->deniedDecision('Link voter reason.'));

        try {
            (new SecurityParameterProvider($decorated, $checker))->provide($operation);
            $this->fail('An AccessDeniedException should have been thrown.');
        } catch (AccessDeniedException $exception) {
            $this->assertSame('Access Denied.', $exception->getMessage());
        }
    }

    private function deniedDecision(?string $reason): AccessDecision
    {
        $decision = new AccessDecision();
        $decision->isGranted = false;
        if (null !== $reason) {
            $vote = new Vote();
            $vote->voter = self::class;
            $vote->result = VoterInterface::ACCESS_DENIED;
            $vote->addReason($reason);
            $decision->votes[] = $vote;
        }

        return $decision;
    }
}

interface SecurityParameterResourceAccessCheckerWithDecisionInterface extends ResourceAccessCheckerInterface, AccessDecisionAwareResourceAccessCheckerInterface
{
}

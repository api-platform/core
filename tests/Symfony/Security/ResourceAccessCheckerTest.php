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

namespace ApiPlatform\Tests\Symfony\Security;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Symfony\Security\AccessDecisionAwareResourceAccessCheckerInterface;
use ApiPlatform\Symfony\Security\Core\Authorization\ExpressionLanguageProvider;
use ApiPlatform\Symfony\Security\ResourceAccessChecker;
use ApiPlatform\Tests\Fixtures\Serializable;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceAccessCheckerTest extends TestCase
{
    use ProphecyTrait;

    #[DataProvider('getGranted')]
    public function testIsGranted(bool $granted): void
    {
        $expressionLanguageProphecy = $this->prophesize(ExpressionLanguage::class);
        $expressionLanguageProphecy->evaluate('is_granted("ROLE_ADMIN")', Argument::type('array'))->willReturn($granted)->shouldBeCalled();

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->willImplement(Serializable::class);
        $token = $tokenProphecy->reveal();
        $tokenProphecy->getUser()->shouldBeCalled();

        $tokenProphecy->getRoleNames()->willReturn([])->shouldBeCalled();

        $tokenStorageProphecy->getToken()->willReturn($token);

        $checker = new ResourceAccessChecker($expressionLanguageProphecy->reveal(), $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $this->assertSame($granted, $checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")'));
    }

    public static function getGranted(): array
    {
        return [[true], [false]];
    }

    public function testSecurityComponentNotAvailable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "symfony/security" library must be installed to use the "security" attribute.');

        $checker = new ResourceAccessChecker($this->prophesize(ExpressionLanguage::class)->reveal());
        $checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")');
    }

    public function testExpressionLanguageNotInstalled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "symfony/expression-language" library must be installed to use the "security" attribute.');

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($this->prophesize(TokenInterface::class)->willImplement(Serializable::class)->reveal());

        $checker = new ResourceAccessChecker(null, $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")');
    }

    public function testUsesObjectVariableThrowsWhenSecurityComponentNotAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "symfony/security" library must be installed to use the "security" attribute.');

        $checker = new ResourceAccessChecker($this->prophesize(ExpressionLanguage::class)->reveal());
        $checker->usesObjectVariable('user == object.owner');
    }

    public function testUsesObjectVariableThrowsWhenExpressionLanguageNotInstalled(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "symfony/expression-language" library must be installed to use the "security" attribute.');

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->willImplement(Serializable::class);
        $tokenProphecy->getUser()->willReturn(null);
        $tokenProphecy->getRoleNames()->willReturn([]);
        $tokenStorageProphecy->getToken()->willReturn($tokenProphecy->reveal());

        $checker = new ResourceAccessChecker(null, $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal());
        $checker->usesObjectVariable('user == object.owner');
    }

    public function testWithoutAuthenticationToken(): void
    {
        $expressionLanguageProphecy = $this->prophesize(ExpressionLanguage::class);
        $expressionLanguageProphecy->evaluate('is_granted("ROLE_ADMIN")', Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $authenticationTrustResolverProphecy = $this->prophesize(AuthenticationTrustResolverInterface::class);
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);

        $tokenStorageProphecy->getToken()->willReturn(null);

        $checker = new ResourceAccessChecker($expressionLanguageProphecy->reveal(), $authenticationTrustResolverProphecy->reveal(), null, $tokenStorageProphecy->reveal(), $authorizationCheckerProphecy->reveal());
        $this->assertTrue($checker->isGranted(Dummy::class, 'is_granted("ROLE_ADMIN")'));
    }

    public function testCapturesASingleDeniedAuthorizationMessage(): void
    {
        $checker = self::createResourceAccessChecker([
            'A' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason A.']]],
        ]);

        $this->assertInstanceOf(AccessDecisionAwareResourceAccessCheckerInterface::class, $checker);
        $decision = $checker->decide(Dummy::class, "is_granted('A', object)", ['object' => new \stdClass()]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame('Access Denied. Reason A.', $decision->getMessage());
    }

    public function testAndShortCircuitsAfterTheFirstDenial(): void
    {
        $calls = [];
        $checker = self::createResourceAccessChecker([
            'A' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason A.']]],
            'B' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason B.']]],
        ], $calls);

        $decision = $checker->decide(Dummy::class, "is_granted('A', object) && is_granted('B', object)", ['object' => new \stdClass()]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame(['A'], $calls);
        $this->assertSame('Access Denied. Reason A.', $decision->getMessage());
    }

    public function testAndAggregatesDeniedVotesAcrossAuthorizationChecks(): void
    {
        $calls = [];
        $checker = self::createResourceAccessChecker([
            'A' => [true, [
                [VoterInterface::ACCESS_DENIED, 'A minority denial.'],
                [VoterInterface::ACCESS_GRANTED, 'A grant.'],
            ]],
            'B' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason B.']]],
        ], $calls);

        $decision = $checker->decide(Dummy::class, "is_granted('A', object) && is_granted('B', object)", ['object' => new \stdClass()]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame(['A', 'B'], $calls);
        $this->assertSame('Access Denied. A minority denial. Reason B.', $decision->getMessage());
    }

    public function testOrAggregatesBothDecisionsWhenBothDeny(): void
    {
        $calls = [];
        $checker = self::createResourceAccessChecker([
            'A' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason A.']]],
            'B' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason B.']]],
        ], $calls);

        $decision = $checker->decide(Dummy::class, "is_granted('A', object) || is_granted('B', object)", ['object' => new \stdClass()]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame(['A', 'B'], $calls);
        $this->assertSame('Access Denied. Reason A. Reason B.', $decision->getMessage());
    }

    public function testNegatedGrantExposesNoDeniedMessage(): void
    {
        $checker = self::createResourceAccessChecker([
            'A' => [true, [[VoterInterface::ACCESS_GRANTED, 'Reason A.']]],
        ]);

        $decision = $checker->decide(Dummy::class, "!is_granted('A', object)", ['object' => new \stdClass()]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame('Access Denied.', $decision->getMessage());
    }

    public function testNonAuthorizationConditionAfterAGrantExposesNoDeniedMessage(): void
    {
        $checker = self::createResourceAccessChecker([
            'A' => [true, [[VoterInterface::ACCESS_GRANTED, 'Reason A.']]],
        ]);
        $object = new class {
            public bool $enabled = false;
        };

        $decision = $checker->decide(Dummy::class, "is_granted('A', object) && object.enabled", ['object' => $object]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame('Access Denied.', $decision->getMessage());
    }

    public function testAuthorizationDenialBeforeAnObjectConditionExposesItsMessage(): void
    {
        $calls = [];
        $checker = self::createResourceAccessChecker([
            'A' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason A.']]],
        ], $calls);
        $object = new class {
            public bool $enabled = false;
        };

        $decision = $checker->decide(Dummy::class, "is_granted('A', object) && object.enabled", ['object' => $object]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame(['A'], $calls);
        $this->assertSame('Access Denied. Reason A.', $decision->getMessage());
    }

    public function testPureNonAuthorizationDenialReturnsAnInitializedDecision(): void
    {
        $calls = [];
        $checker = self::createResourceAccessChecker([], $calls);
        $object = new class {
            public bool $enabled = false;
        };

        $decision = $checker->decide(Dummy::class, 'object.enabled', ['object' => $object]);

        $this->assertFalse($decision->isGranted);
        $this->assertSame([], $calls);
        $this->assertSame('Access Denied.', $decision->getMessage());
    }

    public function testNonAuthorizationConditionCanGrantAfterAnAuthorizationDenial(): void
    {
        $calls = [];
        $checker = self::createResourceAccessChecker([
            'A' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason A.']]],
        ], $calls);
        $object = new class {
            public bool $owner = true;
        };

        $decision = $checker->decide(Dummy::class, "is_granted('A', object) || object.owner", ['object' => $object]);

        $this->assertTrue($decision->isGranted);
        $this->assertSame(['A'], $calls);
        $this->assertSame('Access Granted.', $decision->getMessage());
    }

    public function testDeniedDecisionWithoutReasonExposesTheGenericMessage(): void
    {
        $checker = self::createResourceAccessChecker([
            'A' => [false, []],
        ]);

        $decision = $checker->decide(Dummy::class, "is_granted('A')");

        $this->assertFalse($decision->isGranted);
        $this->assertSame('Access Denied.', $decision->getMessage());
    }

    public function testKeepsIndependentDeniedDecisionsSeparate(): void
    {
        $checker = self::createResourceAccessChecker([
            'A' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason A.']]],
            'B' => [false, [[VoterInterface::ACCESS_DENIED, 'Reason B.']]],
        ]);

        $first = $checker->decide(Dummy::class, "is_granted('A')");
        $second = $checker->decide(Dummy::class, "is_granted('B')");

        $this->assertNotSame($first, $second);
        $this->assertFalse($first->isGranted);
        $this->assertFalse($second->isGranted);
        $this->assertSame('Access Denied. Reason A.', $first->getMessage());
        $this->assertSame('Access Denied. Reason B.', $second->getMessage());
    }

    /**
     * @param array<string, array{bool, list<array{VoterInterface::ACCESS_*, string}>}> $decisions
     * @param list<mixed>                                                               $calls
     */
    private static function createResourceAccessChecker(array $decisions, array &$calls = []): ResourceAccessChecker
    {
        $authorizationChecker = self::createAuthorizationChecker(static function (mixed $attribute, mixed $subject, ?AccessDecision $accessDecision) use ($decisions, &$calls): bool {
            $calls[] = $attribute;
            [$granted, $votes] = $decisions[$attribute];

            foreach ($votes as [$result, $reason]) {
                $accessDecision->votes[] = self::createVote($result, $reason);
            }

            return $granted;
        });

        return self::createResourceAccessCheckerWithAuthorizationChecker($authorizationChecker);
    }

    private static function createResourceAccessCheckerWithAuthorizationChecker(?AuthorizationCheckerInterface $authorizationChecker): ResourceAccessChecker
    {
        return new ResourceAccessChecker(new ExpressionLanguage(null, [new ExpressionLanguageProvider()]), new AuthenticationTrustResolver(), null, new TokenStorage(), $authorizationChecker);
    }

    private static function createAuthorizationChecker(\Closure $callback): AuthorizationCheckerInterface
    {
        return new class($callback) implements AuthorizationCheckerInterface {
            public function __construct(private readonly \Closure $callback)
            {
            }

            public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
            {
                return ($this->callback)($attribute, $subject, $accessDecision);
            }
        };
    }

    /**
     * @param VoterInterface::ACCESS_* $result
     */
    private static function createVote(int $result, string $reason): Vote
    {
        $vote = new Vote();
        $vote->voter = self::class;
        $vote->result = $result;
        $vote->addReason($reason);

        return $vote;
    }
}

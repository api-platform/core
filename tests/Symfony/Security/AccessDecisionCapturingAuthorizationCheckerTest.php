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

use ApiPlatform\Symfony\Security\AccessDecisionCapturingAuthorizationChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Strategy\ConsensusStrategy;
use Symfony\Component\Security\Core\Authorization\Strategy\UnanimousStrategy;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class AccessDecisionCapturingAuthorizationCheckerTest extends TestCase
{
    public function testItPreservesArgumentsAndResult(): void
    {
        $subject = new \stdClass();
        $decorated = self::createAuthorizationChecker(function (mixed $attribute, mixed $actualSubject, ?AccessDecision $accessDecision) use ($subject): bool {
            $this->assertSame('ATTRIBUTE', $attribute);
            $this->assertSame($subject, $actualSubject);
            $this->assertInstanceOf(AccessDecision::class, $accessDecision);

            return true;
        });

        $checker = new AccessDecisionCapturingAuthorizationChecker($decorated);

        $this->assertTrue($checker->isGranted('ATTRIBUTE', $subject));
        $this->assertNull($checker->getAccessDeniedMessage());
    }

    public function testItCreatesOneFreshDecisionPerInvocation(): void
    {
        $decisions = [];
        $decorated = self::createAuthorizationChecker(static function (mixed $attribute, mixed $subject, ?AccessDecision $accessDecision) use (&$decisions): bool {
            $decisions[] = $accessDecision;

            return false;
        });

        $checker = new AccessDecisionCapturingAuthorizationChecker($decorated);
        $checker->isGranted('A');
        $checker->isGranted('B');

        $this->assertCount(2, $decisions);
        $this->assertNotSame($decisions[0], $decisions[1]);
    }

    public function testItUsesAnExplicitlySuppliedDecision(): void
    {
        $decision = new AccessDecision();
        $decorated = self::createAuthorizationChecker(function (mixed $attribute, mixed $subject, ?AccessDecision $actualDecision) use ($decision): bool {
            $this->assertSame($decision, $actualDecision);

            return false;
        });

        $checker = new AccessDecisionCapturingAuthorizationChecker($decorated);

        $this->assertFalse($checker->isGranted('ATTRIBUTE', null, $decision));
        $this->assertFalse($decision->isGranted);
        $this->assertSame('Access Denied.', $checker->getAccessDeniedMessage());
    }

    public function testItUsesSymfonyToFormatReasonsFromMatchingVotes(): void
    {
        $authorizationChecker = self::createSymfonyAuthorizationChecker([
            self::createVoter(VoterInterface::ACCESS_DENIED, 'First reason.'),
            self::createVoter(VoterInterface::ACCESS_GRANTED, 'Granted reason.'),
            self::createVoter(VoterInterface::ACCESS_DENIED, 'Second reason.'),
        ], new ConsensusStrategy(false, false));

        $checker = new AccessDecisionCapturingAuthorizationChecker($authorizationChecker);

        $this->assertFalse($checker->isGranted('ATTRIBUTE'));
        $this->assertSame('Access Denied. First reason. Second reason.', $checker->getAccessDeniedMessage());
    }

    public function testItSelectsOnlyTheLastIndependentDeniedDecision(): void
    {
        $decorated = self::createAuthorizationChecker(static function (mixed $attribute, mixed $subject, ?AccessDecision $accessDecision): bool {
            $accessDecision->votes[] = self::createVote(VoterInterface::ACCESS_DENIED, $attribute.' reason.');

            return false;
        });

        $checker = new AccessDecisionCapturingAuthorizationChecker($decorated);
        $checker->isGranted('A');
        $checker->isGranted('B');

        $this->assertSame('Access Denied. B reason.', $checker->getAccessDeniedMessage());
    }

    public function testALaterGrantLeavesNoDeniedMessage(): void
    {
        $decorated = self::createAuthorizationChecker(static function (mixed $attribute, mixed $subject, ?AccessDecision $accessDecision): bool {
            $granted = 'B' === $attribute;
            $accessDecision->votes[] = self::createVote($granted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED, $attribute.' reason.');

            return $granted;
        });

        $checker = new AccessDecisionCapturingAuthorizationChecker($decorated);
        $checker->isGranted('A');
        $checker->isGranted('B');

        $this->assertNull($checker->getAccessDeniedMessage());
    }

    public function testItRecordsTheResultWhenACustomCheckerIgnoresTheDecision(): void
    {
        $checker = new AccessDecisionCapturingAuthorizationChecker(self::createAuthorizationChecker(static fn (): bool => false));

        $this->assertFalse($checker->isGranted('ATTRIBUTE'));
        $this->assertSame('Access Denied.', $checker->getAccessDeniedMessage());
    }

    public function testNestedSymfonyAuthorizationUsesTheTopLevelDecision(): void
    {
        $outerVoter = new class implements VoterInterface {
            private AuthorizationCheckerInterface $authorizationChecker;

            public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
            {
                $this->authorizationChecker = $authorizationChecker;
            }

            public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
            {
                if ('OUTER' !== $attributes[0]) {
                    return self::ACCESS_ABSTAIN;
                }

                $this->authorizationChecker->isGranted('INNER');
                $vote?->addReason('Outer reason.');

                return self::ACCESS_DENIED;
            }
        };
        $innerVoter = self::createAttributeVoter([
            'INNER' => [VoterInterface::ACCESS_DENIED, 'Inner reason.'],
        ]);
        $authorizationChecker = self::createSymfonyAuthorizationChecker([$outerVoter, $innerVoter], new UnanimousStrategy());
        $outerVoter->setAuthorizationChecker($authorizationChecker);

        $checker = new AccessDecisionCapturingAuthorizationChecker($authorizationChecker);

        $this->assertFalse($checker->isGranted('OUTER'));
        $this->assertSame('Access Denied. Inner reason. Outer reason.', $checker->getAccessDeniedMessage());
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
     * @param list<VoterInterface> $voters
     */
    private static function createSymfonyAuthorizationChecker(array $voters, ConsensusStrategy|UnanimousStrategy $strategy): AuthorizationCheckerInterface
    {
        return new AuthorizationChecker(new TokenStorage(), new AccessDecisionManager($voters, $strategy));
    }

    private static function createVoter(int $result, string $reason): VoterInterface
    {
        return new class($result, $reason) implements VoterInterface {
            public function __construct(private readonly int $result, private readonly string $reason)
            {
            }

            public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
            {
                $vote?->addReason($this->reason);

                return $this->result;
            }
        };
    }

    /**
     * @param array<string, array{VoterInterface::ACCESS_*, string}> $votes
     */
    private static function createAttributeVoter(array $votes): VoterInterface
    {
        return new class($votes) implements VoterInterface {
            /**
             * @param array<string, array{VoterInterface::ACCESS_*, string}> $votes
             */
            public function __construct(private readonly array $votes)
            {
            }

            public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
            {
                if (!isset($this->votes[$attributes[0]])) {
                    return self::ACCESS_ABSTAIN;
                }

                [$result, $reason] = $this->votes[$attributes[0]];
                $vote?->addReason($reason);

                return $result;
            }
        };
    }

    private static function createVote(int $result, string $reason): Vote
    {
        $vote = new Vote();
        $vote->voter = self::class;
        $vote->result = $result;
        $vote->addReason($reason);

        return $vote;
    }
}

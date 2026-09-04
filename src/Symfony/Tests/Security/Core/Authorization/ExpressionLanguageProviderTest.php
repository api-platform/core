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

namespace ApiPlatform\Tests\Symfony\Security\Core\Authorization;

use ApiPlatform\Symfony\Security\Core\Authorization\ExpressionLanguageProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ExpressionLanguageProviderTest extends TestCase
{
    public function testPassesTheAccessDecisionToTheAuthorizationChecker(): void
    {
        $decision = new AccessDecision();
        $authorizationChecker = new RecordingAuthorizationChecker();

        $result = self::createExpressionLanguage()->evaluate('is_granted("A")', [
            'access_decision' => $decision,
            'auth_checker' => $authorizationChecker,
        ]);

        $this->assertTrue($result);
        $this->assertSame($decision, $authorizationChecker->accessDecision);
    }

    public function testDefaultsToNoAccessDecisionOutsideApiPlatform(): void
    {
        $authorizationChecker = new RecordingAuthorizationChecker();

        $result = self::createExpressionLanguage()->evaluate('is_granted("A")', [
            'auth_checker' => $authorizationChecker,
        ]);

        $this->assertTrue($result);
        $this->assertNull($authorizationChecker->accessDecision);
    }

    public function testCompilerPassesTheOptionalAccessDecision(): void
    {
        $compiled = self::createExpressionLanguage()->compile('is_granted("A")', ['access_decision', 'auth_checker']);

        $this->assertStringContainsString('$auth_checker->isGranted("A", null, $access_decision ?? null)', $compiled);
    }

    private static function createExpressionLanguage(): ExpressionLanguage
    {
        return new ExpressionLanguage(null, [new ExpressionLanguageProvider()]);
    }
}

final class RecordingAuthorizationChecker implements AuthorizationCheckerInterface
{
    public ?AccessDecision $accessDecision = null;

    public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
    {
        $this->accessDecision = $accessDecision;

        return true;
    }
}

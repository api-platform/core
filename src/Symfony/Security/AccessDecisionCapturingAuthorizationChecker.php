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

namespace ApiPlatform\Symfony\Security;

use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
final class AccessDecisionCapturingAuthorizationChecker implements AuthorizationCheckerInterface
{
    private ?AccessDecision $accessDecision = null;

    public function __construct(private readonly AuthorizationCheckerInterface $decorated)
    {
    }

    public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
    {
        $accessDecision ??= new AccessDecision();
        $accessDecision->isGranted = $this->decorated->isGranted($attribute, $subject, $accessDecision);
        $this->accessDecision = $accessDecision;

        return $accessDecision->isGranted;
    }

    public function getAccessDeniedMessage(): ?string
    {
        if (null === $this->accessDecision || $this->accessDecision->isGranted) {
            return null;
        }

        return $this->accessDecision->getMessage();
    }
}

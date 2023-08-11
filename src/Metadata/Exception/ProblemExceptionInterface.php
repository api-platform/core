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

namespace ApiPlatform\Metadata\Exception;

/**
 * Implements the Problem Error specification.
 */
interface ProblemExceptionInterface
{
    public function getType(): string;

    /**
     * Note from RFC rfc7807: "title" (string) - A short, human-readable summary of the problem type.
     * It SHOULD NOT change from occurrence to occurrence of the problem, except for purposes of localization.
     */
    public function getTitle(): ?string;

    public function getStatus(): ?int;

    public function getDetail(): ?string;

    public function getInstance(): ?string;
}

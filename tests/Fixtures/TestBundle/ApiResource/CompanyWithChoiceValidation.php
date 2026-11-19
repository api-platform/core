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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [new GetCollection(provider: [CompanyWithChoiceValidation::class, 'provide'])])]
class CompanyWithChoiceValidation
{
    public ?int $id = null;

    #[Assert\Choice(choices: ['SARL', 'SAS', 'SA'])]
    public ?string $companyType = null;

    #[Assert\Choice(callback: [self::class, 'getCompanyTypeChoices'])]
    public ?string $companyTypeFromCallback = null;

    /** @var string[] */
    #[Assert\Choice(choices: ['SARL', 'SAS', 'SA'], multiple: true, min: 1, max: 3)]
    public array $allowedCompanyTypes = [];

    public static function getCompanyTypeChoices(): array
    {
        return ['SARL', 'SAS', 'SA', 'EURL'];
    }

    public static function provide(): array
    {
        return [];
    }
}

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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource]
#[Get(uriTemplate: '/company_badges/{code}')]
#[Put(
    uriTemplate: '/companies-by-name/{name}/badges/{code}',
    allowCreate: true,
    uriVariables: [
        'name' => new Link(identifiers: ['name'], fromClass: Company::class, toProperty: 'company'),
        'code' => new Link(fromClass: self::class),
    ],
)]
#[ODM\Document]
class CompanyBadge
{
    #[ODM\Id(type: 'string', strategy: 'NONE')]
    public string $code;

    #[ODM\ReferenceOne(targetDocument: Company::class, storeAs: 'id')]
    public ?Company $company = null;

    #[ODM\Field]
    public string $label;
}

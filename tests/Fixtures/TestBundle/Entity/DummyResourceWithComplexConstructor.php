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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[Post]
#[ApiResource(
    uriTemplate: '/companies/{companyId}/employees/{id}',
    uriVariables: [
        'companyId' => ['from_class' => Company::class, 'to_property' => 'company'],
        'id' => ['from_class' => DummyResourceWithComplexConstructor::class],
    ]
)]
#[Get]
class DummyResourceWithComplexConstructor
{
    private \DateTimeInterface $someInternalTimestamp;
    private ?Company $company;

    public function __construct(private int $id, private string $name)
    {
        $this->someInternalTimestamp = new \DateTimeImmutable();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSomeInternalTimestamp(): \DateTimeInterface
    {
        return $this->someInternalTimestamp;
    }

    public function setSomeInternalTimestamp(\DateTimeInterface $timestamp): void
    {
        $this->someInternalTimestamp = $timestamp;
    }
}

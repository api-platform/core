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

use ApiPlatform\Doctrine\Odm\Filter\DateFilter as OdmDateFilter;
use ApiPlatform\Doctrine\Odm\State\Options as OdmOptions;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\AgentDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Agent;

#[ApiFilter(DateFilter::class, properties: ['birthday'], alias: 'app_filter_date')]
#[ApiResource(
    shortName: 'Agent',
    operations: [
        new GetCollection(parameters: [
            'birthday' => new QueryParameter(filter: 'app_filter_date'),
        ]),
    ],
    stateOptions: new Options(entityClass: Agent::class)
)]
#[ApiFilter(OdmDateFilter::class, properties: ['birthday'], alias: 'app_filter_date_odm')]
#[ApiResource(
    shortName: 'AgentDocument',
    operations: [
        new GetCollection(parameters: [
            'birthday' => new QueryParameter(filter: 'app_filter_date_odm'),
        ]),
    ],
    stateOptions: new OdmOptions(documentClass: AgentDocument::class)
)]
class AgentApi
{
    private ?int $id = null;

    private ?string $name = null;

    private ?\DateTimeInterface $birthday = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }
}

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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue7913;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'issue_7913_agent')]
#[ApiResource(
    shortName: 'Issue7913Agent',
    operations: [new Get(uriTemplate: '/issue7913_agents/{agentId}', uriVariables: ['agentId'])],
)]
class Agent
{
    #[ODM\Id(type: 'string', strategy: 'INCREMENT')]
    #[ApiProperty(identifier: false)]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[ApiProperty(identifier: true)]
    private ?string $agentId = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAgentId(): ?string
    {
        return $this->agentId;
    }

    public function setAgentId(string $agentId): self
    {
        $this->agentId = $agentId;

        return $this;
    }
}

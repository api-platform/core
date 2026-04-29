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

use ApiPlatform\Doctrine\Odm\Filter\IriFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'issue_7913_mail')]
#[ApiResource(
    shortName: 'Issue7913Mail',
    operations: [
        new GetCollection(
            uriTemplate: '/issue7913_mails',
            normalizationContext: ['hydra_prefix' => false],
            parameters: [
                'author' => new QueryParameter(filter: new IriFilter()),
            ],
        ),
    ],
)]
class Mail
{
    #[ODM\Id(type: 'string', strategy: 'INCREMENT')]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: Agent::class, storeAs: 'id')]
    private ?Agent $author = null;

    #[ODM\Field(type: 'string')]
    private ?string $subject = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAuthor(): ?Agent
    {
        return $this->author;
    }

    public function setAuthor(?Agent $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}

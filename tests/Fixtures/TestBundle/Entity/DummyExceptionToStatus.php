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

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Exception\NotFoundException;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\RequiredFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\State\DummyExceptionToStatusProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[ApiResource(
    exceptionToStatus: [NotFoundHttpException::class => 400],
    provider: DummyExceptionToStatusProvider::class,
    operations: [
        new Get(uriTemplate: '/dummy_exception_to_statuses/{id}', exceptionToStatus: [NotFoundException::class => 404]),
        new Put(uriTemplate: '/dummy_exception_to_statuses/{id}'),
        new GetCollection(uriTemplate: '/dummy_exception_to_statuses'),
    ]
)]
#[ApiFilter(RequiredFilter::class)]
#[ORM\Entity]
class DummyExceptionToStatus
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var string|null The dummy name
     */
    #[ORM\Column(nullable: true)]
    private ?string $name = null;

    /**
     * @var string|null The dummy title
     */
    #[ORM\Column(nullable: true)]
    private ?string $title = null;

    /**
     * @var string The dummy code
     */
    #[ORM\Column]
    private string $code;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }
}

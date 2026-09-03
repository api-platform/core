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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    uriTemplate: 'parameter_on_properties_with_header_parameter',
    operations: [
        new GetCollection(),
        new Get(),
    ]
)]
class ParameterOnPropertiesWithHeaderParameter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', name: 'auth_token')]
    #[HeaderParameter(key: 'X-Authorization', description: 'Authorization header')]
    public string $authToken = '';

    public function __construct(string $authToken = '')
    {
        $this->authToken = $authToken;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}

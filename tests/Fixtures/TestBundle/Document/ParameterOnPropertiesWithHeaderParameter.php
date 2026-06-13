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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ApiResource(
    uriTemplate: 'parameter_on_properties_with_header_parameter',
    operations: [
        new GetCollection(),
        new Get(),
    ]
)]
class ParameterOnPropertiesWithHeaderParameter
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[HeaderParameter(key: 'X-Authorization', description: 'Authorization header')]
    public string $authToken = '';

    public function __construct(string $authToken = '')
    {
        $this->authToken = $authToken;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}

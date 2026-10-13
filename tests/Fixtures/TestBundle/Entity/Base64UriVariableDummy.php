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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
// The plain Get must come first so IRI generation resolves to {id}, not the encodedName template.
#[Get]
#[Get(
    uriTemplate: '/base64_uri_variable_dummies/encoded/{encodedName}',
    uriVariables: [
        'encodedName' => new Link(
            fromClass: self::class,
            identifiers: ['name'],
            provider: [self::class, 'decodeName'],
        ),
    ],
)]
class Base64UriVariableDummy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column]
    public string $name;

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $context
     */
    public static function decodeName(Parameter $parameter, array $parameters = [], array $context = []): void
    {
        $parameter->setValue(base64_decode((string) $parameter->getValue(), true));
    }
}

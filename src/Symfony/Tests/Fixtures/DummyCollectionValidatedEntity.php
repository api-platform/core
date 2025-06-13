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

namespace ApiPlatform\Symfony\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

class DummyCollectionValidatedEntity
{
    /**
     * @var array
     */
    #[Assert\Collection(
        allowExtraFields: true,
        fields: [
            'name' => new Assert\Required([
                new Assert\NotBlank(),
            ]),
            'email' => [
                new Assert\NotNull(),
                new Assert\Length(min: 2, max: 255),
                new Assert\Email(mode: Assert\Email::VALIDATION_MODE_HTML5),
            ],
            'phone' => new Assert\Optional([
                new Assert\Type(type: 'string'),
                new Assert\Regex(pattern: "/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/"),
            ]),
            'age' => new Assert\Optional([
                new Assert\Type(type: 'int'),
                new Assert\GreaterThan(0),
            ]),
            'social' => new Assert\Collection(
                fields: [
                    'githubUsername' => new Assert\NotNull(),
                ]
            ),
        ]
    )]
    public $dummyData;
}

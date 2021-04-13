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

namespace ApiPlatform\Core\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

class DummyCollectionValidatedEntity
{
    /**
     * @var array
     *
     * @Assert\Collection(
     *     allowExtraFields=true,
     *     fields={
     *         "name"=@Assert\Required({
     *             @Assert\NotBlank
     *         }),
     *         "email"={
     *             @Assert\NotNull,
     *             @Assert\Length(min=2, max=255),
     *             @Assert\Email(mode=Assert\Email::VALIDATION_MODE_LOOSE)
     *         },
     *         "phone"=@Assert\Optional({
     *             @Assert\Type(type="string"),
     *             @Assert\Regex(pattern="/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/")
     *         }),
     *         "age"=@Assert\Optional({
     *             @Assert\Type(type="int")
     *         }),
     *         "social"=@Assert\Collection(
     *             fields={
     *                 "githubUsername"=@Assert\NotNull
     *             }
     *         )
     *     }
     * )
     */
    public $dummyData;
}

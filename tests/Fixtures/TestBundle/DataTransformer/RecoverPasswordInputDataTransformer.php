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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\User as UserDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;

final class RecoverPasswordInputDataTransformer implements DataTransformerInterface
{
    /**
     * @return object
     */
    public function transform($data, string $to, array $context = [])
    {
        // Because we're in a PUT operation, we will use the retrieved object...
        $resourceObject = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] ?? new $context['resource_class']();
        // ...where we remove the credentials
        $resourceObject->eraseCredentials();

        return $resourceObject;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (User::class === $to || UserDocument::class === $to) && RecoverPasswordInput::class === ($context['input']['class'] ?? null);
    }
}

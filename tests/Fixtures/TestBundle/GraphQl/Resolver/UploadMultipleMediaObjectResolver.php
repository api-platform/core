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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\GraphQl\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\MediaObject;

/**
 * Resolver for custom multi file upload mutation.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class UploadMultipleMediaObjectResolver implements MutationResolverInterface
{
    /**
     * @param \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\MediaObject|null $item
     *
     * @return \ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\MediaObject|null The mutated item
     */
    public function __invoke($item, array $context): MediaObject
    {
        $result = [];
        //doing some process on uploaded files
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        foreach ($context['args']['input']['files'] as $key => $file) {
            $mediaObject = new MediaObject();
            $mediaObject->id = $key;
            $mediaObject->contentUrl = $file->getFilename();
            $result[] = $mediaObject;
        }

        // Currently api platform does not support custom mutation with collections so for now
        // we are returning last created media object
        return $mediaObject;
    }
}

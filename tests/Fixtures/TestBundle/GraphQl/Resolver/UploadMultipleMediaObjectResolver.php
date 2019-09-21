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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Model\MediaObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Resolver for custom multi file upload mutation.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class UploadMultipleMediaObjectResolver implements MutationResolverInterface
{
    /**
     * @param MediaObject|null $item
     */
    public function __invoke($item, array $context): MediaObject
    {
        $result = [];
        $mediaObject = null;

        /**
         * @var UploadedFile[]
         */
        $uploadedFiles = $context['args']['input']['files'];

        // Some process to save the files.

        foreach ($context['args']['input']['files'] as $key => $uploadedFile) {
            $mediaObject = new MediaObject();
            $mediaObject->id = $key;
            $mediaObject->contentUrl = $uploadedFile->getFilename();
            $result[] = $mediaObject;
        }

        // Currently API Platform does not support custom mutation with collections so for now, we are returning the last created media object.
        return $mediaObject;
    }
}

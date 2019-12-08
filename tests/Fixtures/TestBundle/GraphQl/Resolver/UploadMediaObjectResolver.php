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
 * Resolver for custom file upload mutation.
 *
 * @author Mahmood Bazdar <mahmood@bazdar.me>
 */
class UploadMediaObjectResolver implements MutationResolverInterface
{
    /**
     * @param MediaObject|null $item
     */
    public function __invoke($item, array $context): MediaObject
    {
        /**
         * @var UploadedFile
         */
        $uploadedFile = $context['args']['input']['file'];

        // Some process to save the file.

        $uploadedMediaObject = new MediaObject();
        $uploadedMediaObject->id = 1;
        $uploadedMediaObject->contentUrl = $uploadedFile->getFileName();

        return $uploadedMediaObject;
    }
}

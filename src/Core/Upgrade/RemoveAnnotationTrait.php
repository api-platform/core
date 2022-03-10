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

namespace ApiPlatform\Core\Upgrade;

use phpDocumentor\Reflection\DocBlock\Serializer;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Comment\Doc;

trait RemoveAnnotationTrait
{
    private function removeAnnotationByTag(Doc $comment, string $tagName): Doc
    {
        $factory = DocBlockFactory::createInstance();
        $docBlock = $factory->create($comment->getText());
        foreach ($docBlock->getTagsByName($tagName) as $tag) {
            $docBlock->removeTag($tag);
        }

        $serializer = new Serializer(0, '', true, null, null, \PHP_EOL);

        return new Doc($serializer->getDocComment($docBlock));
    }
}

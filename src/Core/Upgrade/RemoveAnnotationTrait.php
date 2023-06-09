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

use PhpParser\Comment\Doc;

trait RemoveAnnotationTrait
{
    private function removeAnnotationByTag(Doc $comment, string $tagName): Doc
    {
        // https://regex101.com/r/GBIPnj/1
        $text = preg_filter("/^[[:blank:]]*[\/*]+[[:blank:]]*@{$tagName}[[:blank:]]*(\(.*?\))?[\s*\/]*?$/ms", '', $comment->getText());

        return null === $text ? $comment : new Doc($text);
    }
}

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

namespace ApiPlatform\Serializer;

/**
 * Generic item normalizer.
 *
 * TODO: do not hardcode "id"
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @todo Denormalization methods should be deprecated in 5.x, use ItemDenormalizer instead
 */
class ItemNormalizer extends AbstractItemNormalizer
{
    use ItemNormalizerTrait;
}

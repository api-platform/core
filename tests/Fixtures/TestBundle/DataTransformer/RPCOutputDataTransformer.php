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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RPC as RPCDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RPCOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RPC;

final class RPCOutputDataTransformer implements DataTransformerInterface
{
    /**
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        return new RPCOutput();
    }

    public function supportsTransformation($object, string $to, array $context = []): bool
    {
        return ($object instanceof RPC || $object instanceof RPCDocument) && RPCOutput::class === $to;
    }
}

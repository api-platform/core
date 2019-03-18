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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryResolverInterface;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCustomQuery;

/**
 * Resolver for dummy custom query (@link DummyCustomQuery).
 *
 * @author Lukas Lücke <lukas@luecke.me>
 */
class DummyCustomQueryResolver implements QueryResolverInterface
{
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function __invoke($source, $args, $context, ResolveInfo $info)
    {
    	$dummy = new DummyCustomQuery();
    	$dummy->message = "Success!";
        return $this->normalizer->normalize($dummy, ItemNormalizer::FORMAT);
    }
}


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

namespace ApiPlatform\Elasticsearch\State;

use ApiPlatform\Elasticsearch\Metadata\Operation as ElasticsearchOperation;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\Util\ElasticsearchVersion;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Item provider for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ItemProvider implements ProviderInterface
{
    public function __construct(private readonly Client $client, private readonly DenormalizerInterface $denormalizer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if (!$operation instanceof ElasticsearchOperation) {
            throw new \InvalidArgumentException(sprintf('$operation must be instance of %s, but %s given', ElasticsearchOperation::class, $operation::class));
        }

        try {
            $params = [
                'index' => $operation->getIndex(),
                'id' => (string) reset($uriVariables),
            ];
            if (ElasticsearchVersion::supportsMappingType()) {
                $params['type'] = $operation->getType();
            }

            $document = $this->client->get($params);
        } catch (Missing404Exception) {
            return null;
        }

        $item = $this->denormalizer->denormalize($document, $operation->getClass(), DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
        if (!\is_object($item) && null !== $item) {
            throw new \UnexpectedValueException('Expected item to be an object or null.');
        }

        return $item;
    }
}

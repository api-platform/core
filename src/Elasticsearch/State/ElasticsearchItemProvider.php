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

use ApiPlatform\Elasticsearch\Metadata\ElasticsearchDocument;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\Util\ElasticsearchVersion;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\Inflector;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class ElasticsearchItemProvider implements ProviderInterface
{
    public function __construct(private readonly Client $client, private readonly DenormalizerInterface $denormalizer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $documentConfiguration = $operation->getPersistenceMeans();
        if (!$documentConfiguration instanceof ElasticsearchDocument) {
            throw new \LogicException(sprintf('Operation‘s persistence means must be instance of %s, but got %s', ElasticsearchDocument::class, get_debug_type($documentConfiguration)));
        }

        $params = [
            'index' => $documentConfiguration->index ?? Inflector::tableize($operation->getShortName()),
            'id' => (string) reset($uriVariables),
        ];
        if (ElasticsearchVersion::supportsMappingType()) {
            $params['type'] = $documentConfiguration->type;
        }

        try {
            $document = $this->client->get($params);
        } catch (Missing404Exception) {
            return null;
        }

        return $this->denormalizer->denormalize($document, $operation->getClass(), DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
    }
}

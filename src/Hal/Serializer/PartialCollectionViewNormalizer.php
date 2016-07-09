<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\RequestParser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Adds a view key to the result of a paginated Hydra collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PartialCollectionViewNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use ContextTrait;

    private $collectionNormalizer;
    private $pageParameterName;
    private $enabledParameterName;
    private $formats;

    public function __construct(NormalizerInterface $collectionNormalizer, string $pageParameterName, string $enabledParameterName, array $formats)
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->pageParameterName = $pageParameterName;
        $this->enabledParameterName = $enabledParameterName;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);

        if ($paginated = $object instanceof PaginatorInterface) {
            $currentPage = $object->getCurrentPage();
            $lastPage = $object->getLastPage();

            if (1. === $currentPage && 1. === $lastPage) {
                // Consider the collection not paginated if there is only one page
                $paginated = false;
            }
        }

        list($parts, $parameters) = $this->parseRequestUri($context['request_uri'] ?? '/');
        $appliedFilters = $parameters;
        unset($appliedFilters[$this->enabledParameterName]);

        if ([] === $appliedFilters && !$paginated) {
            return $data;
        }

        if ('jsonhal' === $format) {
            return $data;
        }


        return $data;
    }

    /**
     * Parses and standardizes the request URI.
     */
    private function parseRequestUri(string $requestUri) : array
    {
        $parts = parse_url($requestUri);
        if (false === $parts) {
            throw new InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $requestUri));
        }

        $parameters = [];
        if (isset($parts['query'])) {
            $parameters = RequestParser::parseRequestParams($parts['query']);

            // Remove existing page parameter
            unset($parameters[$this->pageParameterName]);
        }

        return [$parts, $parameters];
    }

    /**
     * Gets a collection @id for the given parameters.
     */
    private function getId(array $parts, array $parameters, float $page = null) : string
    {
        if (null !== $page) {
            $parameters[$this->pageParameterName] = $page;
        }

        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $parts['query'] = preg_replace('/%5B[0-9]+%5D/', '%5B%5D', $query);

        $url = $parts['path'];

        if ('' !== $parts['query']) {
            $url .= '?'.$parts['query'];
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if ($this->collectionNormalizer instanceof SerializerAwareInterface) {
            $this->collectionNormalizer->setSerializer($serializer);
        }
    }
}

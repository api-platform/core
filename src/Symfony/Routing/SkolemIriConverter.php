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

namespace ApiPlatform\Symfony\Routing;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class SkolemIriConverter implements IriConverterInterface
{
    public static $skolemUriTemplate = '/.well-known/genid/{id}';

    private $objectHashMap;
    private $classHashMap = [];
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
        $this->objectHashMap = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null)
    {
        throw new ItemNotFoundException(sprintf('Item not found for "%s".', $iri));
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResource($item, int $referenceType = UrlGeneratorInterface::ABS_PATH, Operation $operation = null, array $context = []): ?string
    {
        $referenceType = $operation ? ($operation->getUrlGenerationStrategy() ?? $referenceType) : $referenceType;
        if (($isObject = \is_object($item)) && $this->objectHashMap->contains($item)) {
            return $this->router->generate('api_genid', ['id' => $this->objectHashMap[$item]], $referenceType);
        }

        if (\is_string($item) && isset($this->classHashMap[$item])) {
            return $this->router->generate('api_genid', ['id' => $this->classHashMap[$item]], $referenceType);
        }

        $id = bin2hex(random_bytes(10));

        if ($isObject) {
            $this->objectHashMap[$item] = $id;
        } else {
            $this->classHashMap[$item] = $id;
        }

        return $this->router->generate('api_genid', ['id' => $id], $referenceType);
    }
}

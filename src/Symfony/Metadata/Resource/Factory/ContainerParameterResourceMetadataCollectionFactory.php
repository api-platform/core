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

namespace ApiPlatform\Symfony\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Metadata;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\ContainerParameterResolver;
use Psr\Container\ContainerInterface;

/**
 * Resolves Symfony container parameters (%param%) declared in attribute-defined resource metadata.
 *
 * Attribute arguments are raw PHP literals, so #[ApiResource(security: '%app.security%')] reaches
 * the metadata as the literal string "%app.security%". This decorator walks the produced collection
 * and substitutes those parameters, matching the YAML/XML extractors. The container is only
 * available in the Symfony bridge, which is why this factory lives here and not in the
 * dependency-injection-free metadata component.
 *
 * Two resolution rules apply, mirroring the extractors:
 * - scalar string fields (uriTemplate, routePrefix, host, controller, provider, processor,
 *   securityMessage, …) resolve %param% anywhere in the string;
 * - ExpressionLanguage fields (security, securityPostDenormalize, securityPostValidation,
 *   condition) resolve only when the whole trimmed value is a single %param% reference, so partial
 *   uses and real modulo expressions reach the expression engine untouched.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ContainerParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * Scalar string fields resolved anywhere in the value.
     *
     * @var array<string, string> getter => setter
     */
    private const SCALAR_FIELDS = [
        'getShortName' => 'withShortName',
        'getDescription' => 'withDescription',
        'getUriTemplate' => 'withUriTemplate',
        'getRoutePrefix' => 'withRoutePrefix',
        'getRouteName' => 'withRouteName',
        'getHost' => 'withHost',
        'getController' => 'withController',
        'getSecurityMessage' => 'withSecurityMessage',
        'getSecurityPostDenormalizeMessage' => 'withSecurityPostDenormalizeMessage',
        'getSecurityPostValidationMessage' => 'withSecurityPostValidationMessage',
        'getProvider' => 'withProvider',
        'getProcessor' => 'withProcessor',
    ];

    /**
     * ExpressionLanguage fields resolved only when the whole value is a single %param% reference.
     *
     * @var array<string, string> getter => setter
     */
    private const EXPRESSION_FIELDS = [
        'getSecurity' => 'withSecurity',
        'getSecurityPostDenormalize' => 'withSecurityPostDenormalize',
        'getSecurityPostValidation' => 'withSecurityPostValidation',
        'getCondition' => 'withCondition',
    ];

    private readonly ContainerParameterResolver $resolver;

    public function __construct(
        ContainerInterface $container,
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
        $this->resolver = new ContainerParameterResolver($container);
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = $resource->getOperations();
            if (null !== $operations) {
                $newOperations = new Operations();
                foreach ($operations as $operationName => $operation) {
                    $newOperations->add($operationName, $this->resolveMetadata($operation));
                }

                $resource = $resource->withOperations($newOperations);
            }

            $resourceMetadataCollection[$i] = $this->resolveMetadata($resource);
        }

        return $resourceMetadataCollection;
    }

    /**
     * @template T of Metadata
     *
     * @param T $metadata
     *
     * @return T
     */
    private function resolveMetadata(Metadata $metadata): Metadata
    {
        foreach (self::SCALAR_FIELDS as $getter => $setter) {
            if (!method_exists($metadata, $getter)) {
                continue;
            }

            $value = $metadata->{$getter}();
            if (!\is_string($value)) {
                continue;
            }

            $resolved = $this->resolver->resolve($value);
            if ($resolved !== $value) {
                $metadata = $metadata->{$setter}($resolved);
            }
        }

        foreach (self::EXPRESSION_FIELDS as $getter => $setter) {
            if (!method_exists($metadata, $getter)) {
                continue;
            }

            $value = $metadata->{$getter}();
            $resolved = $this->resolver->resolveExpressionPlaceholder($value);
            if ($resolved !== $value) {
                $metadata = $metadata->{$setter}($resolved);
            }
        }

        return $metadata;
    }
}

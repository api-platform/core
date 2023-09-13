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

namespace ApiPlatform\Symfony\Bundle\ArgumentResolver;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class PayloadArgumentResolver implements CompatibleValueResolverInterface
{
    use OperationRequestInitiatorTrait;

    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly SerializerContextBuilderInterface $serializationContextBuilder
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->isVariadic()) {
            return false;
        }

        $class = $argument->getType();

        if (null === $class) {
            return false;
        }

        if (null === $request->attributes->get('data')) {
            return false;
        }

        $inputClass = $this->getExpectedInputClass($request);

        if (null === $inputClass) {
            return false;
        }

        return $inputClass === $class || is_subclass_of($inputClass, $class);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (interface_exists(ValueResolverInterface::class)) {
            if (!$this->supports($request, $argument)) {
                return [];
            }
        }

        yield $request->attributes->get('data');
    }

    private function getExpectedInputClass(Request $request): ?string
    {
        $operation = $this->initializeOperation($request);
        if (Request::METHOD_DELETE === $request->getMethod() || $request->isMethodSafe() || !($operation?->canDeserialize() ?? true)) {
            return null;
        }

        $context = $this->serializationContextBuilder->createFromRequest($request, false, RequestAttributesExtractor::extractAttributes($request));

        return $context['input']['class'] ?? $context['resource_class'] ?? null;
    }
}

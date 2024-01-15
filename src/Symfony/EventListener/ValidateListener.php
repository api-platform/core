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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Validates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidateListener
{
    use OperationRequestInitiatorTrait;

    public const OPERATION_ATTRIBUTE_KEY = 'validate';

    private ValidatorInterface $validator;
    private ?ProviderInterface $provider = null;

    public function __construct(ProviderInterface|ValidatorInterface $validator, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
        if ($validator instanceof ProviderInterface) {
            $this->provider = $validator;
        } else {
            trigger_deprecation('api-platform/core', '3.3', 'Use a "%s" as first argument in "%s" instead of "%s".', ProviderInterface::class, self::class, ValidatorInterface::class);
            $this->validator = $validator;
        }

        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * Validates data returned by the controller if applicable.
     *
     * @throws ValidationException
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        if ($operation && $this->provider instanceof ProviderInterface) {
            if (null === $operation->canValidate()) {
                $operation = $operation->withValidate(!$request->isMethodSafe() && !$request->isMethod('DELETE'));
            }

            $this->provider->provide($operation, $request->attributes->get('_api_uri_variables') ?? [], [
                'request' => $request,
                'uri_variables' => $request->attributes->get('_api_uri_variables') ?? [],
                'resource_class' => $operation->getClass(),
            ]);

            return;
        }

        if ('api_platform.symfony.main_controller' === $operation?->getController() || $request->attributes->get('_api_platform_disable_listeners')) {
            return;
        }

        if (
            $controllerResult instanceof Response
            || $request->isMethodSafe()
            || $request->isMethod('DELETE')
        ) {
            return;
        }

        if (!$operation || !($operation->canValidate() ?? true)) {
            return;
        }

        $this->validator->validate($controllerResult, $operation->getValidationContext() ?? []);
    }
}

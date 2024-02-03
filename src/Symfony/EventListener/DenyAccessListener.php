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
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access to the current resource if the logged user doesn't have sufficient permissions.
 *
 * @deprecated use ApiPlatform\Symfony\Security\State\AccessCheckerProvider instead
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DenyAccessListener
{
    use OperationRequestInitiatorTrait;

    public function __construct(?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function onSecurity(RequestEvent $event): void
    {
        $this->checkSecurity($event->getRequest(), 'security');
    }

    public function onSecurityPostDenormalize(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->checkSecurity($request, 'security_post_denormalize', [
            'previous_object' => $request->attributes->get('previous_data'),
        ]);
    }

    public function onSecurityPostValidation(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $this->checkSecurity($request, 'security_post_validation', [
            'previous_object' => $request->attributes->get('previous_data'),
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    private function checkSecurity(Request $request, string $attribute, array $extraVariables = []): void
    {
        if ($request->attributes->get('_api_platform_disable_listeners') || !$this->resourceAccessChecker || !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $operation = $this->initializeOperation($request);
        if ('api_platform.symfony.main_controller' === $operation?->getController()) {
            return;
        }

        if (!$operation) {
            return;
        }

        switch ($attribute) {
            case 'security_post_denormalize':
                $isGranted = $operation->getSecurityPostDenormalize();
                $message = $operation->getSecurityPostDenormalizeMessage();
                break;
            case 'security_post_validation':
                $isGranted = $operation->getSecurityPostValidation();
                $message = $operation->getSecurityPostValidationMessage();
                break;
            default:
                $isGranted = $operation->getSecurity();
                $message = $operation->getSecurityMessage();
        }

        if (null === $isGranted) {
            return;
        }

        $extraVariables += $request->attributes->all();
        $extraVariables['object'] = $request->attributes->get('data');
        $extraVariables['previous_object'] = $request->attributes->get('previous_data');
        $extraVariables['request'] = $request;

        if (!$this->resourceAccessChecker->isGranted($attributes['resource_class'], $isGranted, $extraVariables)) {
            throw new AccessDeniedException($message ?? 'Access Denied.');
        }
    }
}

<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\RateLimit\RateLimiterInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function array_diff;
use function implode;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass used to configure the rate limiters and pass them to the listener
 *
 * @author Toby Griffiths <toby@cubicmushroom.co.uk>
 */
class RateLimitPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('api_platform.rate_limit.enabled')) {
            return;
        }

        $limitsConfig = $container->getParameter('api_platform.rate_limit.limits');

        $limiterServiceIds = array_keys($container->findTaggedServiceIds('api_platform.rate_limiter'));

        $configuredLimiterServiceConfigKeysByServiceId = array_reduce(
            $limiterServiceIds,
            function (array $configuredLimiterServices, string $limiterServiceId) use ($container, $limitsConfig) {

                $limiterDefinition = $container->getDefinition($limiterServiceId);
                /** @var RateLimiterInterface $limiterClass */
                $limiterClass     = $limiterDefinition->getClass();
                $limiterConfigKey = $limiterClass::getConfigKey();

                if (isset($limitsConfig[$limiterConfigKey])) {
                    $configuredLimiterServices[$limiterServiceId] = $limiterConfigKey;
                }

                return $configuredLimiterServices;

            },
            []
        );

        if (count($limitsConfig) !== count($configuredLimiterServiceConfigKeysByServiceId)) {
            $extraConfig = array_diff(array_keys($limitsConfig), array_values($configuredLimiterServiceConfigKeysByServiceId));

            throw new RuntimeException(
                'Rate limited services not found for configs… \'' . implode('\', \'', $extraConfig), '\''
            );
        }

        if (!count($configuredLimiterServiceConfigKeysByServiceId)) {
            throw new RuntimeException('Rate limiter enabled, but no limiters are configured.');
        }

        $listenerDefinition = $container->getDefinition('api_platform.listener.rate_limit');
        $priority = $container->getParameter('api_platform.rate_limit.priority');

        $listenerDefinition->addArgument('%api_platform.rate_limit.limits%');

        $listenerDefinition->addTag(
            'kernel.event_listener',
            [
                'event'    => 'kernel.request',
                'method'   => 'onKernelRequest',
                'priority' => $priority,
            ]
        );

        foreach (array_keys($configuredLimiterServiceConfigKeysByServiceId) as $serviceId) {
            $listenerDefinition->addMethodCall('addRateLimiters', [new Reference($serviceId)]);
        }
    }
}

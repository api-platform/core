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

namespace ApiPlatform\GraphQl\Resolver\Factory;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\GraphQl\Resolver\Stage\DeserializeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostValidationStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\ValidateStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\WriteStageInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Util\ClassInfoTrait;
use ApiPlatform\Util\CloneTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;

/**
 * Creates a function resolving a GraphQL mutation of an item.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ItemMutationResolverFactory implements ResolverFactoryInterface
{
    use ClassInfoTrait;
    use CloneTrait;

    private $readStage;
    private $securityStage;
    private $securityPostDenormalizeStage;
    private $serializeStage;
    private $deserializeStage;
    private $writeStage;
    private $validateStage;
    private $mutationResolverLocator;
    private $securityPostValidationStage;

    public function __construct(ReadStageInterface $readStage, SecurityStageInterface $securityStage, SecurityPostDenormalizeStageInterface $securityPostDenormalizeStage, SerializeStageInterface $serializeStage, DeserializeStageInterface $deserializeStage, WriteStageInterface $writeStage, ValidateStageInterface $validateStage, ContainerInterface $mutationResolverLocator, SecurityPostValidationStageInterface $securityPostValidationStage)
    {
        $this->readStage = $readStage;
        $this->securityStage = $securityStage;
        $this->securityPostDenormalizeStage = $securityPostDenormalizeStage;
        $this->serializeStage = $serializeStage;
        $this->deserializeStage = $deserializeStage;
        $this->writeStage = $writeStage;
        $this->validateStage = $validateStage;
        $this->mutationResolverLocator = $mutationResolverLocator;
        $this->securityPostValidationStage = $securityPostValidationStage;
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?Operation $operation = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operation) {
            if (null === $resourceClass || null === $operation) {
                return null;
            }

            $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false];

            $item = ($this->readStage)($resourceClass, $rootClass, $operation, $resolverContext);
            if (null !== $item && !\is_object($item)) {
                throw new \LogicException('Item from read stage should be a nullable object.');
            }
            ($this->securityStage)($resourceClass, $operation, $resolverContext + [
                'extra_variables' => [
                    'object' => $item,
                ],
            ]);
            $previousItem = $this->clone($item);

            if ('delete' === $operation->getName() || $operation instanceof DeleteOperationInterface) {
                ($this->securityPostDenormalizeStage)($resourceClass, $operation, $resolverContext + [
                    'extra_variables' => [
                        'object' => $item,
                        'previous_object' => $previousItem,
                    ],
                ]);
                $item = ($this->writeStage)($item, $resourceClass, $operation, $resolverContext);

                return ($this->serializeStage)($item, $resourceClass, $operation, $resolverContext);
            }

            $item = ($this->deserializeStage)($item, $resourceClass, $operation, $resolverContext);

            $mutationResolverId = $operation->getResolver();
            if (null !== $mutationResolverId) {
                /** @var MutationResolverInterface $mutationResolver */
                $mutationResolver = $this->mutationResolverLocator->get($mutationResolverId);
                $item = $mutationResolver($item, $resolverContext);
                if (null !== $item && $resourceClass !== $itemClass = $this->getObjectClass($item)) {
                    throw new \LogicException(sprintf('Custom mutation resolver "%s" has to return an item of class %s but returned an item of class %s.', $mutationResolverId, $operation->getShortName(), (new \ReflectionClass($itemClass))->getShortName()));
                }
            }

            ($this->securityPostDenormalizeStage)($resourceClass, $operation, $resolverContext + [
                'extra_variables' => [
                    'object' => $item,
                    'previous_object' => $previousItem,
                ],
            ]);

            if (null !== $item) {
                ($this->validateStage)($item, $resourceClass, $operation, $resolverContext);

                ($this->securityPostValidationStage)($resourceClass, $operation, $resolverContext + [
                    'extra_variables' => [
                        'object' => $item,
                        'previous_object' => $previousItem,
                    ],
                ]);

                $persistResult = ($this->writeStage)($item, $resourceClass, $operation, $resolverContext);
            }

            return ($this->serializeStage)($persistResult ?? $item, $resourceClass, $operation, $resolverContext);
        };
    }
}

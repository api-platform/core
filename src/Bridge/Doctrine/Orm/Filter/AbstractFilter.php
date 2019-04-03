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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait as OrmPropertyHelperTrait;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestParser;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 *
 * Abstract class with helpers for easing the implementation of a filter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
abstract class AbstractFilter implements FilterInterface
{
    use PropertyHelperTrait;
    use OrmPropertyHelperTrait;

    protected $managerRegistry;
    protected $requestStack;
    protected $logger;
    protected $properties;

    /**
     * @param PropertyMetadataFactoryInterface|null $propertyMetadataFactory
     * @param array|null                            $properties
     */
    public function __construct(ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, LoggerInterface $logger = null, /*PropertyMetadataFactoryInterface*/ $propertyMetadataFactory = null, /*array*/ $properties = null)
    {
        if (null !== $requestStack) {
            @trigger_error(sprintf('Passing an instance of "%s" is deprecated since 2.2. Use "filters" context key instead.', RequestStack::class), E_USER_DEPRECATED);
        }

        if (null === $properties && \is_array($propertyMetadataFactory)) {
            @trigger_error(sprintf('Passing the "$properties" argument as fourth argument to the %s() method is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3. Use the fifth argument instead.', __METHOD__), E_USER_DEPRECATED);

            $properties = $propertyMetadataFactory;
            $propertyMetadataFactory = null;
        }

        if (null !== $properties && !\is_array($properties)) {
            throw new InvalidArgumentException('The "$properties" argument is expected to be an array or null.');
        }

        if (null === $propertyMetadataFactory) {
            @trigger_error(sprintf('Not injecting an implementation of "%s" as fourth argument to the %s() method is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3.', PropertyMetadataFactoryInterface::class, __METHOD__), E_USER_DEPRECATED);
        } elseif (!$propertyMetadataFactory instanceof PropertyMetadataFactoryInterface) {
            throw new InvalidArgumentException(sprintf('The "$propertyMetadataFactory" argument is expected to be an implementation of the "%s" interface.', PropertyMetadataFactoryInterface::class));
        }

        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
        $this->logger = $logger ?? new NullLogger();
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null/*, array $context = []*/)
    {
        @trigger_error(sprintf('Using "%s::apply()" is deprecated since 2.2. Use "%s::apply()" with the "filters" context key instead.', __CLASS__, AbstractContextAwareFilter::class), E_USER_DEPRECATED);

        if (null === $this->requestStack || null === $request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        foreach ($this->extractProperties($request, $resourceClass) as $property => $value) {
            $this->filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);
        }
    }

    /**
     * Passes a property through the filter.
     */
    abstract protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null/*, array $context = []*/);

    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    protected function getProperties(): ?array
    {
        return $this->properties;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Extracts properties to filter from the request.
     */
    protected function extractProperties(Request $request/*, string $resourceClass*/): array
    {
        @trigger_error(sprintf('The use of "%s::extractProperties()" is deprecated since 2.2. Use the "filters" key of the context instead.', __CLASS__), E_USER_DEPRECATED);

        $resourceClass = \func_num_args() > 1 ? (string) func_get_arg(1) : null;
        $needsFixing = false;
        if (null !== $this->properties) {
            foreach ($this->properties as $property => $value) {
                if (($this->isPropertyNested($property, $resourceClass) || $this->isPropertyEmbedded($property, $resourceClass)) && $request->query->has(str_replace('.', '_', $property))) {
                    $needsFixing = true;
                }
            }
        }

        if ($needsFixing) {
            $request = RequestParser::parseAndDuplicateRequest($request);
        }

        return $request->query->all();
    }
}

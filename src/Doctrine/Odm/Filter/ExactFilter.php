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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class ExactFilter implements FilterInterface, OpenApiParameterFilterInterface, ManagerRegistryAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use ManagerRegistryAwareTrait;
    use OpenApiFilterTrait;

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];
        $property = $parameter->getProperty();
        $value = $parameter->getValue();

        $documentManager = $this->getManagerRegistry()->getManagerForClass($resourceClass);
        if (!$documentManager instanceof DocumentManager) {
            return;
        }

        $classMetadata = $documentManager->getClassMetadata($resourceClass);

        if (!$classMetadata->hasReference($property)) {
            $aggregationBuilder
                ->match()
                ->field($property)
                ->{is_iterable($value) ? 'in' : 'equals'}($value);

            return;
        }

        $mapping = $classMetadata->getFieldMapping($property);
        $targetDocument = $mapping['targetDocument'];
        $repository = $documentManager->getRepository($targetDocument);
        $method = $classMetadata->isCollectionValuedAssociation($property) ? 'includesReferenceTo' : 'references';

        if (is_iterable($value)) {
            $documents = [];
            foreach ($value as $v) {
                if ($doc = $repository->find($v)) {
                    $documents[] = $doc;
                }
            }

            $match = $aggregationBuilder->match();
            $or = $match->expr();
            foreach ($documents as $doc) {
                $or->addOr($match->expr()->field($property)->{$method}($doc));
            }
            $match->addAnd($or);

            return;
        }

        $referencedDoc = $repository->find($value);

        $aggregationBuilder
            ->match()
            ->field($property)
            ->{$method}($referencedDoc);
    }
}

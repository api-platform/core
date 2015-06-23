<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\Api;

use Dunglas\ApiBundle\Api\Operation\Operation;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\Routing\Route;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CustomResource implements ResourceInterface
{
    public function getEntityClass()
    {
        return 'Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Custom';
    }

    public function getShortName()
    {
        return 'Custom';
    }

    public function getItemOperations()
    {
        return [new Operation(new Route('/customs/{id}'), 'custom_item')];
    }

    public function getCollectionOperations()
    {
        return [new Operation(new Route('/customs'), 'custom_collection')];
    }

    public function getFilters()
    {
        return [];
    }

    public function getNormalizationContext()
    {
        return [];
    }

    public function getNormalizationGroups()
    {
        return;
    }

    public function getDenormalizationContext()
    {
        return [];
    }

    public function getDenormalizationGroups()
    {
        return;
    }

    public function getValidationGroups()
    {
        return;
    }

    public function isPaginationEnabledByDefault()
    {
        return false;
    }

    public function isClientAllowedToEnablePagination()
    {
        return false;
    }

    public function getItemsPerPageByDefault()
    {
        return 0.;
    }

    public function isClientAllowedToChangeItemsPerPage()
    {
        return false;
    }

    public function getEnablePaginationParameter()
    {
        return '';
    }

    public function getPageParameter()
    {
        return '';
    }

    public function getItemsPerPageParameter()
    {
        return '';
    }
}

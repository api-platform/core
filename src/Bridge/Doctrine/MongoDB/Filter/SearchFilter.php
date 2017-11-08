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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Bundle\MongoDBBundle\Logger\LoggerInterface;
use Doctrine\MongoDB\Query\Builder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class SearchFilter extends AbstractFilter
{

    protected $iriConverter;
    protected $propertyAccessor;
    protected $requestStack;

    public function __construct(IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, RequestStack $requestStack)
    {
        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function getIdFromValue(string $value)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($value, ['fetch_data' => false])) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }

    /**
     * Normalize the values array.
     *
     * @param array $values
     *
     * @return array
     */
    protected function normalizeValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (!is_int($key) || !is_string($value)) {
                unset($values[$key]);
            }
        }

        return array_values($values);
    }

}
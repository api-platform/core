<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\Factory\ItemMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\ItemMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;

/**
 * Decorates a metadata loader using the validator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemMetadataFactory implements ItemMetadataFactoryInterface
{
    /**
     * @var string[] A list of constraint classes making the entity required.
     */
    const REQUIRED_CONSTRAINTS = [NotBlank::class, NotNull::class];

    private $decorated;
    private $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory, ItemMetadataFactoryInterface $decorated)
    {
        $this->metadataFactory = $metadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * Is this constraint making the related property required?
     *
     * @param Constraint $constraint
     *
     * @return bool
     */
    private function isRequired(Constraint $constraint) : bool
    {
        foreach (self::REQUIRED_CONSTRAINTS as $requiredConstraint) {
            if ($constraint instanceof $requiredConstraint) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $name, array $options = []) : ItemMetadata
    {
        $itemMetadata = $this->decorated->create($resourceClass, $name, $options);
        if (null !== $itemMetadata->isRequired()) {
            return $itemMetadata;
        }

        $validatorClassMetadata = $this->metadataFactory->getMetadataFor($resourceClass);

        foreach ($validatorClassMetadata->getPropertyMetadata($name) as $validatorPropertyMetadata) {
            if (isset($options['validation_groups'])) {
                foreach ($options['validation_groups'] as $validationGroup) {
                    if (!is_string($validationGroup)) {
                        continue;
                    }

                    foreach ($validatorPropertyMetadata->findConstraints($validationGroup) as $constraint) {
                        if ($this->isRequired($constraint)) {
                            return $itemMetadata->withRequired(true);
                        }
                    }
                }

                return $itemMetadata->withRequired(false);
            }

            foreach ($validatorPropertyMetadata->findConstraints($validatorClassMetadata->getDefaultGroup()) as $constraint) {
                if ($this->isRequired($constraint)) {
                    return $itemMetadata->withRequired(true);
                }
            }

            return $itemMetadata->withRequired(false);
        }

        return $itemMetadata->withRequired(false);
    }
}

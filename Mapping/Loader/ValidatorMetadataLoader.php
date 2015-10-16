<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;

/**
 * Loads metadata from validator metadata.
 *
 * Attributes must be loaded first.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidatorMetadataLoader implements LoaderInterface
{
    /**
     * @var string[] A list of constraint classes making the entity required.
     */
    public static $requiredConstraints = [
        'Symfony\Component\Validator\Constraints\NotBlank',
        'Symfony\Component\Validator\Constraints\NotNull',
    ];

    /**
     * @var MetadataFactoryInterface
     */
    private $validatorMetadataFactory;

    public function __construct(MetadataFactoryInterface $validatorMetadataFactory)
    {
        $this->validatorMetadataFactory = $validatorMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(
        ClassMetadataInterface $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $validatorClassMetadata = $this->validatorMetadataFactory->getMetadataFor($classMetadata->getName());

        foreach ($classMetadata->getAttributesMetadata() as $attributeName => $attributeMetadata) {
            foreach ($validatorClassMetadata->getPropertyMetadata($attributeName) as $propertyMetadata) {
                if (null === $validationGroups) {
                    foreach ($propertyMetadata->findConstraints($validatorClassMetadata->getDefaultGroup()) as $constraint) {
                        if ($this->isRequired($constraint)) {
                            $attributeMetadata = $attributeMetadata->withRequired(true);

                            break 2;
                        }
                    }
                } else {
                    foreach ($validationGroups as $validationGroup) {
                        foreach ($propertyMetadata->findConstraints($validationGroup) as $constraint) {
                            if ($this->isRequired($constraint)) {
                                $attributeMetadata = $attributeMetadata->withRequired(true);

                                break 3;
                            }
                        }
                    }
                }
            }

            $classMetadata = $classMetadata->withAttributeMetadata($attributeName, $attributeMetadata);
        }

        return $classMetadata;
    }

    /**
     * Is this constraint making the related property required?
     *
     * @param Constraint $constraint
     *
     * @return bool
     */
    private function isRequired(Constraint $constraint)
    {
        foreach (self::$requiredConstraints as $requiredConstraint) {
            if ($constraint instanceof $requiredConstraint) {
                return true;
            }
        }

        return false;
    }
}

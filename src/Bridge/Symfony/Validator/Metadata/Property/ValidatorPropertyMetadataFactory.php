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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\CardScheme;
use Symfony\Component\Validator\Constraints\Currency;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Isbn;
use Symfony\Component\Validator\Constraints\Issn;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Time;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface as ValidatorClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface as ValidatorMetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface as ValidatorPropertyMetadataInterface;

/**
 * Decorates a metadata loader using the validator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidatorPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    /**
     * @var string[] A list of constraint classes making the entity required
     */
    public const REQUIRED_CONSTRAINTS = [NotBlank::class, NotNull::class];

    public const SCHEMA_MAPPED_CONSTRAINTS = [
        Url::class => 'http://schema.org/url',
        Email::class => 'http://schema.org/email',
        Uuid::class => 'http://schema.org/identifier',
        CardScheme::class => 'http://schema.org/identifier',
        Bic::class => 'http://schema.org/identifier',
        Iban::class => 'http://schema.org/identifier',
        Date::class => 'http://schema.org/Date',
        DateTime::class => 'http://schema.org/DateTime',
        Time::class => 'http://schema.org/Time',
        Image::class => 'http://schema.org/image',
        File::class => 'http://schema.org/MediaObject',
        Currency::class => 'http://schema.org/priceCurrency',
        Isbn::class => 'http://schema.org/isbn',
        Issn::class => 'http://schema.org/issn',
    ];

    private $decorated;
    private $validatorMetadataFactory;

    public function __construct(ValidatorMetadataFactoryInterface $validatorMetadataFactory, PropertyMetadataFactoryInterface $decorated)
    {
        $this->validatorMetadataFactory = $validatorMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $name, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $name, $options);

        $required = $propertyMetadata->isRequired();
        $iri = $propertyMetadata->getIri();

        if (null !== $required && null !== $iri) {
            return $propertyMetadata;
        }

        $validatorClassMetadata = $this->validatorMetadataFactory->getMetadataFor($resourceClass);
        if (!$validatorClassMetadata instanceof ValidatorClassMetadataInterface) {
            throw new \UnexpectedValueException(sprintf('Validator class metadata expected to be of type "%s".', ValidatorClassMetadataInterface::class));
        }

        foreach ($validatorClassMetadata->getPropertyMetadata($name) as $validatorPropertyMetadata) {
            if (null === $required && isset($options['validation_groups'])) {
                $required = $this->isRequiredByGroups($validatorPropertyMetadata, $options);
            }

            if (!method_exists($validatorClassMetadata, 'getDefaultGroup')) {
                throw new \UnexpectedValueException(sprintf('Validator class metadata expected to have method "%s".', 'getDefaultGroup'));
            }

            foreach ($validatorPropertyMetadata->findConstraints($validatorClassMetadata->getDefaultGroup()) as $constraint) {
                if (null === $required && $this->isRequired($constraint)) {
                    $required = true;
                }

                if (null === $iri) {
                    $iri = self::SCHEMA_MAPPED_CONSTRAINTS[\get_class($constraint)] ?? null;
                }

                if (null !== $required && null !== $iri) {
                    break 2;
                }
            }
        }

        return $propertyMetadata->withIri($iri)->withRequired($required ?? false);
    }

    /**
     * Tests if the property is required because of its validation groups.
     */
    private function isRequiredByGroups(ValidatorPropertyMetadataInterface $validatorPropertyMetadata, array $options): bool
    {
        foreach ($options['validation_groups'] as $validationGroup) {
            if (!\is_string($validationGroup)) {
                continue;
            }

            foreach ($validatorPropertyMetadata->findConstraints($validationGroup) as $constraint) {
                if ($this->isRequired($constraint)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Is this constraint making the related property required?
     */
    private function isRequired(Constraint $constraint): bool
    {
        foreach (self::REQUIRED_CONSTRAINTS as $requiredConstraint) {
            if ($constraint instanceof $requiredConstraint) {
                return true;
            }
        }

        return false;
    }
}

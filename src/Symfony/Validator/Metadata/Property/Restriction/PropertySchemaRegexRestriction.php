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

namespace ApiPlatform\Symfony\Validator\Metadata\Property\Restriction;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class PropertySchemaRegexRestriction.
 *
 * @author Andrii Penchuk penja7@gmail.com
 */
class PropertySchemaRegexRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Regex $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        if (null !== ($htmlPattern = $constraint->getHtmlPattern())) {
            return ['pattern' => '^('.$htmlPattern.')$'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        return $constraint instanceof Regex;
    }
}

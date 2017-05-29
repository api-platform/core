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

namespace ApiPlatform\Core\Serializer\Filter;

use Symfony\Component\HttpFoundation\Request;

/**
 * Group filter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class GroupFilter implements FilterInterface
{
    private $overrideDefaultGroups;
    private $parameterName;

    public function __construct(string $parameterName = 'groups', bool $overrideDefaultGroups = false)
    {
        $this->overrideDefaultGroups = $overrideDefaultGroups;
        $this->parameterName = $parameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, bool $normalization, array $attributes, array &$context)
    {
        if (!is_array($groups = $request->query->get($this->parameterName))) {
            return;
        }

        if (!$this->overrideDefaultGroups && isset($context['groups'])) {
            $groups = array_merge((array) $context['groups'], $groups);
        }

        $context['groups'] = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        return [
            $this->parameterName.'[]' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
            ],
        ];
    }
}

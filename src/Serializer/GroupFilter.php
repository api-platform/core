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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\FilterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Group filter allows to add dynamic filters via query parameters.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class GroupFilter implements FilterInterface, SerializerContextBuilderInterface
{
    private $decorated;
    private $parameterName;

    public function __construct(SerializerContextBuilderInterface $decorated, $parameterName = 'groups')
    {
        $this->decorated = $decorated;
        $this->parameterName = $parameterName;
    }

    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        $groups = $request->query->get($this->parameterName);

        if (!$groups) {
            return $context;
        }

        if (!is_array($groups)) {
            $groups = [$groups];
        }

        $context['groups'] = array_merge($groups, $context['groups'] ?? []);

        return $context;
    }

    public function getDescription(string $resourceClass): array
    {
        return [$this->parameterName => [
            'property' => $this->parameterName,
            'type' => 'array',
            'required' => false,
        ]];
    }
}

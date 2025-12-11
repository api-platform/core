<?php

namespace ApiPlatform\Mcp\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * @implements ProviderInterface<object>
 */
final class ToolProvider implements ProviderInterface
{
    public function __construct(private readonly ObjectMapperInterface $objectMapper)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!isset($context['mcp_request'])) {
            return null;
        }

        $data = (object) $context['mcp_data'];
        $class = $operation->getInput()['class'] ?? $operation->getClass();
        return $this->objectMapper->map($data, $class);
    }
}

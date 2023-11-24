<?php

namespace ApiPlatform\Metadata;

use ApiPlatform\OpenApi;

final class Parameter {
    public string $key;
    public \ArrayObject $schema;
    public array $context;
    public ?OpenApi\Model\Parameter $openApi;
    /**
     * @param fn(mixed $value, Parameter $parameter, array $context)|string|null
     */
    public mixed $provider;
}

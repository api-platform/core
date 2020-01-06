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

namespace ApiPlatform\Core\OpenApi\Model;

class Components
{
    use ExtensionTrait;

    private $schemas;
    private $responses;
    private $parameters;
    private $examples;
    private $requestBodies;
    private $headers;
    private $securitySchemes;
    private $links;
    private $callbacks;

    public function __construct(array $schemas = [], array $responses = [], array $parameters = [], array $examples = [], array $requestBodies = [], array $headers = [], array $securitySchemes = [], array $links = [], array $callbacks = [])
    {
        $this->schemas = $schemas;
        $this->responses = $responses;
        $this->parameters = $parameters;
        $this->examples = $examples;
        $this->requestBodies = $requestBodies;
        $this->headers = $headers;
        $this->securitySchemes = $securitySchemes;
        $this->links = $links;
        $this->callbacks = $callbacks;
    }

    public function getSchemas()
    {
        return $this->schemas;
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getExamples()
    {
        return $this->examples;
    }

    public function getRequestBodies()
    {
        return $this->requestBodies;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getSecuritySchemes()
    {
        return $this->securitySchemes;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function getCallbacks()
    {
        return $this->callbacks;
    }
}

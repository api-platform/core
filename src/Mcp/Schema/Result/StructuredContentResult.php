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

namespace ApiPlatform\Mcp\Schema\Result;

use Mcp\Schema\JsonRpc\ResultInterface;

class StructuredContentResult implements ResultInterface
{
    /**
     * Create a new StructuredContentResult.
     *
     * @param array           $structuredContent The JSON content
     * @param ResultInterface $result            A traditional result
     */
    public function __construct(
        public array $structuredContent,
        public readonly ?ResultInterface $result = null,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return ['structuredContent' => $this->structuredContent] + ($this->result?->jsonSerialize() ?? []);
    }
}

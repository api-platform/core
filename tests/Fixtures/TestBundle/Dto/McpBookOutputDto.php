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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\McpBook as McpBookEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: McpBookEntity::class)]
final class McpBookOutputDto
{
    public int $id;

    public string $name;

    public string $isbn;

    public static function fromMcpBook(McpBookEntity $mcpBook): self
    {
        $mcpBookOutputDto = new self();
        $mcpBookOutputDto->id = $mcpBook->getId();
        $mcpBookOutputDto->name = $mcpBook->getTitle();
        $mcpBookOutputDto->isbn = $mcpBook->getIsbn();

        return $mcpBookOutputDto;
    }
}

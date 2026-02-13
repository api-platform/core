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

namespace Workbench\App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\McpTool;
use Illuminate\Database\Eloquent\Model;

#[ApiResource(
    shortName: 'McpBook',
    operations: [],
    mcp: [
        'get_book_info' => new McpTool(
            provider: [self::class, 'provide']
        ),
        'update_book_status' => new McpTool(
            processor: [self::class, 'process']
        ),
    ]
)]
class McpBook extends Model
{
    protected $fillable = ['title', 'isbn', 'status'];

    public ?string $title = null;
    public ?string $isbn = null;
    public ?string $status = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
        $this->setAttribute('title', $title);
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): void
    {
        $this->isbn = $isbn;
        $this->setAttribute('isbn', $isbn);
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
        $this->setAttribute('status', $status);
    }

    public static function provide(): self
    {
        $book = new self();
        $book->setTitle('API Platform Guide');
        $book->setIsbn('978-1234567890');
        $book->setStatus('available');

        return $book;
    }

    public static function process($data): mixed
    {
        $data->setStatus('updated');

        return $data;
    }
}

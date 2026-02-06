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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\McpBookOutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\McpBook;
use Doctrine\Persistence\ManagerRegistry;

class McpBookListDtoProcessor implements ProcessorInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?McpBookOutputDto
    {
        $search = $context['data']->search ?? null;

        $mcpBookRepository = $this->managerRegistry->getRepository(McpBook::class);

        $queryBuilder = $mcpBookRepository->createQueryBuilder('b');
        $queryBuilder
            ->where($queryBuilder->expr()->like('b.title', ':title'))
            ->setParameter(':title', '%'.$search.'%');

        $book = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($book instanceof McpBook) {
            return McpBookOutputDto::fromMcpBook($book);
        }

        return null;
    }
}

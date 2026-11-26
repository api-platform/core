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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Odm;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource\Document;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeMapper;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeObject;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\RuntimeResources;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<RuntimeObject, RuntimeObject|null>
 */
final class DocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly RuntimeMapper $mapper,
        private readonly DocumentProvider $provider,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?RuntimeObject
    {
        $manager = $this->registry->getManagerForClass(Document::class);
        \assert($manager instanceof DocumentManager && $data instanceof RuntimeObject);

        $name = (string) RuntimeResources::nameOf($data::class);
        $document = $operation instanceof Post
            ? new Document($name)
            : $manager->getRepository(Document::class)->findOneBy(['id' => (int) ($uriVariables['id'] ?? 0), 'resource' => $name]) ?? throw new NotFoundHttpException();

        if ($operation instanceof DeleteOperationInterface) {
            $manager->remove($document);
            $manager->flush();

            return null;
        }

        $document->setValues(array_map(static fn (mixed $value): mixed => $value instanceof \DateTimeInterface ? new UTCDateTime($value->getTimestamp() * 1000 + (int) $value->format('v')) : $value, $this->mapper->extract($data)));
        $manager->persist($document);
        $manager->flush();

        return $this->provider->find($name, (int) $document->getId());
    }
}

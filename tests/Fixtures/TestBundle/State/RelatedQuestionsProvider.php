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
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Question as QuestionDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Question;
use Doctrine\Persistence\ManagerRegistry;

class RelatedQuestionsProvider implements ProviderInterface
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $manager = $this->registry->getManagerForClass($operation->getClass());
        $repository = $manager->getRepository($operation->getClass());
        /** @var Question|QuestionDocument */
        $question = $repository->findOneBy(['id' => 1]);

        return $question->getAnswer()->getRelatedQuestions();
    }

    // public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    // {
    //     return (QuestionDocument::class === $resourceClass || Question::class === $resourceClass) && '_api_/questions/{id}/answer/related_questions_get_collection' === $operationName;
    // }
}

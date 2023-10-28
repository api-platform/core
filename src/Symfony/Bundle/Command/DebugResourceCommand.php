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

namespace ApiPlatform\Symfony\Bundle\Command;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;

final class DebugResourceCommand extends Command
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ClonerInterface $cloner, private $dumper)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Debug API Platform resources')
            ->addArgument('class', InputArgument::REQUIRED, 'The class you want to debug');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resourceClass = $input->getArgument('class');

        $resourceCollection = $this->resourceMetadataCollectionFactory->create($resourceClass);

        if (0 === \count($resourceCollection)) {
            $output->writeln(sprintf('<error>No resources found for class %s</error>', $resourceClass));

            return \defined(Command::class.'::INVALID') ? Command::INVALID : 2;
        }

        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $resources = [];
        foreach ($resourceCollection as $resource) {
            if ($resource->getUriTemplate()) {
                $resources[] = $resource->getUriTemplate();
                continue;
            }

            foreach ($resource->getOperations() as $operation) {
                if ($operation->getUriTemplate()) {
                    $resources[] = $operation->getUriTemplate();
                    break;
                }
            }
        }

        if (\count($resourceCollection) > 1) {
            $questionResource = new ChoiceQuestion(
                sprintf('There are %d resources declared on the class %s, which one do you want to debug ? ', \count($resourceCollection), $shortName).\PHP_EOL,
                $resources
            );

            $answerResource = $helper->ask($input, $output, $questionResource);
            $resourceIndex = array_search($answerResource, $resources, true);
            $selectedResource = $resourceCollection[$resourceIndex];
        } else {
            $selectedResource = $resourceCollection[0];
            $output->writeln(sprintf('Class %s declares 1 resource.', $shortName).\PHP_EOL);
        }

        $operations = ['Debug the resource itself'];
        foreach ($selectedResource->getOperations() as $operationName => $operation) {
            $operations[] = $operationName;
        }

        $questionOperation = new ChoiceQuestion(
            sprintf('There are %d operation%s declared on the resource, which one do you want to debug ? ', $selectedResource->getOperations()->count(), $selectedResource->getOperations()->count() > 1 ? 's' : '').\PHP_EOL,
            $operations
        );

        $answerOperation = $helper->ask($input, $output, $questionOperation);
        if ('Debug the resource itself' === $answerOperation) {
            $this->dumper->dump($this->cloner->cloneVar($selectedResource));
            $output->writeln('Successfully dumped the selected resource');

            return \defined(Command::class.'::SUCCESS') ? Command::SUCCESS : 0;
        }

        $this->dumper->dump($this->cloner->cloneVar($resourceCollection->getOperation($answerOperation)));
        $output->writeln('Successfully dumped the selected operation');

        return \defined(Command::class.'::SUCCESS') ? Command::SUCCESS : 0;
    }

    public static function getDefaultName(): string
    {
        return 'debug:api-resource';
    }
}

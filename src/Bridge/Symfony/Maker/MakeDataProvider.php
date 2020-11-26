<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class MakeDataProvider extends AbstractMaker
{
    /**
     * {@inheritDoc}
     */
    public static function getCommandName(): string
    {
        return 'make:data-provider';
    }

    /**
     * {@inheritDoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a new class to provide data')
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your data provider (e.g. <fg=yellow>AwesomeDataProvider</>)')
            ->setHelp(file_get_contents(__DIR__.'/Resources/help/MakeDataProvider.txt'))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $dataPersisterClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'DataProvider\\'
        );
        $generator->generateClass(
            $dataPersisterClassNameDetails->getFullName(),
            __DIR__.'/Resources/skeleton/DataProvider.tpl.php'
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
        $io->text([
            'Next: Open your new data provider class and start customizing it.',
            'Find the documentation at <fg=yellow>https://api-platform.com/docs/core/data-providers/#data-providers</>',
        ]);
    }
}

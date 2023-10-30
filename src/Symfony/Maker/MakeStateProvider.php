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

namespace ApiPlatform\Symfony\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

final class MakeStateProvider extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:state-provider';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates an API Platform state provider';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('name', InputArgument::REQUIRED, 'Choose a class name for your state provider (e.g. <fg=yellow>AwesomeStateProvider</>)')
            ->setHelp(file_get_contents(__DIR__.'/Resources/help/MakeStateProvider.txt'));
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $stateProviderClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'State\\'
        );

        $generator->generateClass(
            $stateProviderClassNameDetails->getFullName(),
            __DIR__.'/Resources/skeleton/StateProvider.tpl.php'
        );
        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: Open your new state provider class and start customizing it.',
        ]);
    }
}

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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Symfony\Maker\Enum\SupportedFilterTypes;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

final class MakeFilter extends AbstractMaker
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandName(): string
    {
        return 'make:filter';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCommandDescription(): string
    {
        return 'Creates an API Platform filter';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('type', InputArgument::REQUIRED, \sprintf('Choose a type for your filter (<fg=yellow>%s</>)', self::getFilterTypesAsString()))
            ->addArgument('name', InputArgument::REQUIRED, 'Choose a class name for your filter (e.g. <fg=yellow>AwesomeFilter</>)')
            ->setHelp(file_get_contents(__DIR__.'/Resources/help/MakeFilter.txt'));
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $typeArgument = strtolower((string) $input->getArgument('type'));
        $type = SupportedFilterTypes::tryFrom($typeArgument);
        if (null === $type) {
            throw new InvalidArgumentException(\sprintf('The type "%s" is not a valid filter type, valid options are: %s.', $typeArgument, self::getFilterTypesAsString()));
        }

        $filterNameDetails = $generator->createClassNameDetails(
            name: $input->getArgument('name'),
            namespacePrefix: 'Filter\\'
        );
        $filterName = \sprintf('%sFilter', ucfirst($type->value));

        $generator->generateClass(className: $filterNameDetails->getFullName(), templateName: \sprintf(
            '%s/Resources/skeleton/%s.php.tpl',
            __DIR__,
            $filterName
        ));

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: Open your filter class and start customizing it.',
        ]);
    }

    private static function getFilterTypesAsString(): string
    {
        $validOptions = array_column(SupportedFilterTypes::cases(), 'value');

        return implode(' or ', array_map('strtoupper', $validOptions));
    }
}

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

use DateTimeImmutable;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use function count;
use function sprintf;
use Symfony\Component\Validator\Constraints\NotBlank;

final class MakeApiResource extends AbstractMaker
{
    private const OPERATION_CHOICES = [
        'Get',
        'GetCollection',
        'Post',
        'Put',
        'Patch',
        'Delete',
    ];

    private const FIELD_TYPES = [
        'string',
        'int',
        'float',
        'bool',
        'array',
        DateTimeImmutable::class,
    ];

    public function __construct(private readonly string $namespacePrefix = '')
    {
    }

    public static function getCommandName(): string
    {
        return 'make:api-resource';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates an API Platform resource';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::REQUIRED, 'Choose a class name for your API resource (e.g. <fg=yellow>BookResource</>)')
            ->addOption('namespace-prefix', 'p', InputOption::VALUE_REQUIRED, 'Specify the namespace prefix to use for the resource class', $this->namespacePrefix.'ApiResource')
            ->setHelp(file_get_contents(__DIR__.'/Resources/help/MakeApiResource.txt'));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $namespacePrefix = trim($input->getOption('namespace-prefix'), '\\').'\\';

        [$fields, $validatedFields] = $this->getFields($io);

        $operations = $this->getOperations($io);

        [$providerClass, $providerShort] = $this->getStateProvider($io, $input, $generator, $operations);
        [$processorClass, $processorShort] = $this->getStateProcessor($io, $input, $generator, $operations);

        $resourceDetails = $generator->createClassNameDetails($input->getArgument('name'), $namespacePrefix);

        $generator->generateClass(
            $resourceDetails->getFullName(),
            __DIR__.'/Resources/skeleton/ApiResource.php.tpl',
            [
                'fields' => $fields,
                'operations' => $operations,
                'has_validator' => class_exists(NotBlank::class) && count($validatedFields) > 0,
                'validated_fields' => $validatedFields,
                'provider_class' => $providerClass,
                'provider_short' => $providerShort,
                'processor_class' => $processorClass,
                'processor_short' => $processorShort,
            ],
        );

        if ($providerClass) {
            $generator->generateClass(
                $providerClass,
                __DIR__ . '/Resources/skeleton/ApiResourceStateProvider.php.tpl',
                [
                    'operations' => $operations,
                ]
            );
        }

        if ($processorClass) {
            $generator->generateClass(
                $processorClass,
                __DIR__ . '/Resources/skeleton/ApiResourceStateProcessor.php.tpl',
                [
                    'operations' => $operations,
                ]
            );
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $generatedFiles = [$resourceDetails->getFullName()];
        if ($providerClass) {
            $generatedFiles[] = $providerClass;
        }
        if ($processorClass) {
            $generatedFiles[] = $processorClass;
        }

        $io->text([
            'Generated classes:',
            ...array_map(static fn (string $class) => sprintf(' - <info>%s</info>', $class), $generatedFiles),
        ]);
    }

    private function getFields(ConsoleStyle $io): array
    {
        $fields = [];
        $validatedFields = [];
        $io->writeln('');
        $io->writeln('Add fields to your API resource (press <info>enter</info> with an empty name to stop):');
        while (true) {
            $fieldName = $io->ask('Field name (press <info>enter</info> to stop adding fields)');
            if (!$fieldName) {
                break;
            }

            $question = new Question('Field type (enter <comment>?</comment> to see types)', 'string');
            $question->setAutocompleterValues(self::FIELD_TYPES);
            $fieldType = $io->askQuestion($question);

            if ('?' === $fieldType) {
                foreach (self::FIELD_TYPES as $item) {
                    $io->writeln(\sprintf(' * <comment>%s</>', $item));
                }
                $fieldType = null;
                continue;
            }

            if ($fieldType && \class_exists('\\'.$fieldType) && \in_array('\\'.$fieldType, self::FIELD_TYPES, true)) {
                $fieldType = '\\'.$fieldType;
            }

            do {
                if ($fieldType && !\in_array($fieldType, self::FIELD_TYPES, true)) {
                    foreach ($fieldType as $item) {
                        $io->writeln(\sprintf(' * <comment>%s</>', $item));
                    }
                    $io->error(\sprintf('Invalid field type "%s".', $fieldType));
                    $io->writeln('');
                    $fieldType = null;
                }
            } while ($fieldType === null);

            $nullable = $io->confirm('Can this field be null?', false);

            if (!$nullable && $io->confirm('Should this field be validated as not blank/not null?', true)) {
                if (!class_exists(NotBlank::class)) {
                    $io->warning('symfony/validator is not installed. Skipping validation constraint.');
                } else {
                    $validatedFields[] = $fieldName;
                }
            }

            $fields[] = [
                'name' => $fieldName,
                'type' => $fieldType,
                'nullable' => $nullable,
            ];
        }

        return [$fields, $validatedFields];
    }

    /**
     * @return string[] $operations
     */
    private function getOperations(ConsoleStyle $io): array
    {
        $operations = [];

        $io->writeln('');
        $io->writeln('Select operations for your API resource:');
        while (true) {
            $remaining = array_values(array_diff(self::OPERATION_CHOICES, $operations));
            if (0 === count($remaining)) {
                break;
            }

            $question = new Question('Add operation (enter <comment>?</comment> to see all operations, leave empty to skip)');
            $question->setAutocompleterValues($remaining);
            $operation = $io->askQuestion($question);

            if (null === $operation) {
                break;
            }

            if ('?' === $operation) {
                foreach ($remaining as $item) {
                    $io->writeln(\sprintf(' * <comment>%s</>', $item));
                }
                $operation = null;
                continue;
            }

            if ($operation && !\in_array($operation, $remaining, true)) {
                foreach ($remaining as $item) {
                    $io->writeln(\sprintf(' * <comment>%s</>', $item));
                }
                $io->error(\sprintf('Invalid operation "%s".', $operation));
                $io->writeln('');
                $operation = null;
                continue;
            }

            $operations[] = $operation;
            $io->writeln(sprintf(' <info>✓</info> Added <comment>%s</comment> operation', $operation));
        }

        return $operations;
    }

    /**
     * @param string[] $operations
     * @return array [?string, ?string]
     */
    private function getStateProvider(ConsoleStyle $io, InputInterface $input, Generator $generator, array $operations): array
    {
        $providerClass = null;
        $providerShort = null;

        if ($io->confirm('Do you want to create a StateProvider?', false)) {
            $providerName = $input->getArgument('name');
            if (!str_ends_with($providerName, 'Provider')) {
                $providerName .= 'Provider';
            }
            $providerDetails = $generator->createClassNameDetails($providerName, $this->namespacePrefix.'State\\');
            $providerClass = $providerDetails->getFullName();
            $providerShort = $providerDetails->getShortName();
        }

        return [$providerClass, $providerShort];
    }

    /**
     * @param string[] $operations
     * @return array [?string, ?string]
     */
    private function getStateProcessor(ConsoleStyle $io, InputInterface $input, Generator $generator, array $operations): array
    {
        $processorClass = null;
        $processorShort = null;

        if ($io->confirm('Do you want to create a StateProcessor?', false)) {
            $processorName = $input->getArgument('name');
            if (!str_ends_with($processorName, 'Processor')) {
                $processorName .= 'Processor';
            }
            $processorDetails = $generator->createClassNameDetails($processorName, $this->namespacePrefix.'State\\');
            $processorClass = $processorDetails->getFullName();
            $processorShort = $processorDetails->getShortName();
        }

        return [$processorClass, $processorShort];
    }
}

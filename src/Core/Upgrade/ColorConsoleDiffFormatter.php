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

namespace ApiPlatform\Core\Upgrade;

use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Inspired by @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/Differ/DiffConsoleFormatter.php to be
 * used as standalone class, without need to require whole package by Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * Forked by @soyuka from Symplify\PackageBuilder\Console\Formatter to remove Nette\Utils\Strings dependency to be even more standalone.
 *
 * @see \Symplify\PackageBuilder\Tests\Console\Formatter\ColorConsoleDiffFormatterTest
 *
 * @internal
 */
final class ColorConsoleDiffFormatter
{
    /**
     * @var string
     *
     * @see https://regex101.com/r/ovLMDF/1
     */
    private const PLUS_START_REGEX = '#^(\+.*)#';

    /**
     * @var string
     *
     * @see https://regex101.com/r/xwywpa/1
     */
    private const MINUT_START_REGEX = '#^(\-.*)#';

    /**
     * @var string
     *
     * @see https://regex101.com/r/CMlwa8/1
     */
    private const AT_START_REGEX = '#^(@.*)#';

    /**
     * @var string
     *
     * @see https://regex101.com/r/qduj2O/1
     */
    private const NEWLINES_REGEX = "#\n\r|\n#";

    private string $template;

    public function __construct()
    {
        $this->template = sprintf(
            '<comment>    ---------- begin diff ----------</comment>%s%%s%s<comment>    ----------- end diff -----------</comment>'.\PHP_EOL,
            \PHP_EOL,
            \PHP_EOL
        );
    }

    public function format(string $diff): string
    {
        return $this->formatWithTemplate($diff, $this->template);
    }

    private function formatWithTemplate(string $diff, string $template): string
    {
        $escapedDiff = OutputFormatter::escape(rtrim($diff));

        $escapedDiffLines = preg_split(self::NEWLINES_REGEX, $escapedDiff);

        // remove description of added + remove; obvious on diffs
        foreach ($escapedDiffLines as $key => $escapedDiffLine) {
            if ('--- Original' === $escapedDiffLine) {
                unset($escapedDiffLines[$key]);
            }

            if ('+++ New' === $escapedDiffLine) {
                unset($escapedDiffLines[$key]);
            }
        }

        $coloredLines = array_map(function (string $string): string {
            $string = $this->makePlusLinesGreen($string);
            $string = $this->makeMinusLinesRed($string);
            $string = $this->makeAtNoteCyan($string);

            if (' ' === $string) {
                return '';
            }

            return $string;
        }, $escapedDiffLines);

        return sprintf($template, implode(\PHP_EOL, $coloredLines));
    }

    private function makePlusLinesGreen(string $string): string
    {
        return preg_replace(self::PLUS_START_REGEX, '<fg=green>$1</fg=green>', $string);
    }

    private function makeMinusLinesRed(string $string): string
    {
        return preg_replace(self::MINUT_START_REGEX, '<fg=red>$1</fg=red>', $string);
    }

    private function makeAtNoteCyan(string $string): string
    {
        return preg_replace(self::AT_START_REGEX, '<fg=cyan>$1</fg=cyan>', $string);
    }
}

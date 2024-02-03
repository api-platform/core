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

/*
 * This script checks for dependencies between our components
 * and fails if a component has a wrong dependency.
 */
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

$loader = require './vendor/autoload.php';
$namespace = 'ApiPlatform';
$prefix = 'api-platform';
$ignoreList = ['ApiPlatform\\Api', 'ApiPlatform\\Exception', 'ApiPlatform\\Util'];
$stopOnFailure = in_array('--stop-on-failure', $_SERVER['argv'], true);
$skipPaths = [__DIR__.'/src/GraphQl/Resolver/Stage'];

/**
 * Reads the beginning of a PHP class and returns "use" instructions matching our namespace.
 */
$class_uses_namespaces = function (ReflectionClass $r) use ($namespace): \Generator {
    $fp = fopen($r->getFileName(), 'r');
    $u = 'use';
    $c = false;
    while (($buffer = fgets($fp, 4096)) !== false) {
        if ($c && \PHP_EOL === $buffer) {
            break;
        }

        if (!str_starts_with($buffer, $u)) {
            continue;
        }

        $c = true;
        $buffer = substr($buffer, 4, -2);
        if (str_starts_with($buffer, $namespace)) {
            yield substr($buffer, 0, strpos($buffer, ' ') ?: null);
        }
    }

    fclose($fp);
};

// Creates and require the map of dependencies
$directories = [];
$map = [];
foreach (Finder::create()->in('src')->notPath('vendor')->name('composer.json') as $f) {
    if (null === ($component = json_decode($f->getContents(), true))) {
        continue;
    }

    $filter = fn ($v) => str_starts_with($v, $prefix);
    $dependencies =
        array_merge(
            array_filter(array_keys((array) $component['require']), $filter),
            array_filter(array_keys((array) $component['require-dev'] ?? []), $filter)
        );

    $map[$component['name']] = ['namespace' => substr(key($component['autoload']['psr-4']), 0, -1), 'dependencies' => $dependencies];
    $directories[] = substr($f->getRealPath(), 0, -14);

    foreach (Finder::create()->in($f->getPath())->notPath('vendor')->notPath('var')->name('*.php')->notName('*.tpl.php')->notName('bootstrap.php') as $f) {
        require_once $f->getRealPath();
    }
}

// create a PSR map of dependencies
$psrMap = [];
foreach ($map as $component) {
    $depsPsr = [];
    foreach ($component['dependencies'] as $d) {
        $depsPsr[] = $map[$d]['namespace'];
    }

    $psrMap[$component['namespace']] = $depsPsr;
}

$warned = [];
$getComponentNamespace = function (ReflectionClass $r, ?ReflectionClass $inside = null) use ($psrMap, $warned, $ignoreList, $namespace) {
    $ns = $r->getNamespaceName();
    // Find this components namespace
    $nsParts = explode('\\', $ns);
    $n = count($nsParts);
    $componentNs = $nsParts[0].'\\'.$nsParts[1];
    $i = 2;

    while (!isset($psrMap[$componentNs]) && $i < $n) {
        if ($part = ($nsParts[$i++] ?? null)) {
            $componentNs .= '\\'.$part;
        }
    }

    if (!isset($psrMap[$componentNs])) {
        if (in_array($componentNs, $ignoreList, true)) {
            return null;
        }

        $guess = $nsParts[0].'\\'.$nsParts[1];
        if ($warned[$guess] ?? true) {
            echo sprintf('"%s" is not an %s component at "%s" %s', $guess, $namespace, ($inside ?? $r)->getFileName(), \PHP_EOL);
            $warned[$guess] = false;
        }

        return null;
    }

    return $componentNs;
};

$exitCode = 0;
$lnamespace = strlen($namespace);
foreach (array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits()) as $className) {
    $r = new ReflectionClass($className);
    $ns = $r->getNamespaceName();

    foreach ($skipPaths as $base) {
        if (!($fileName = $r->getFileName())) {
            continue;
        }

        if (Path::isBasePath($skipPaths[0], $fileName)) {
            continue 2;
        }
    }

    if (!str_starts_with($ns, $namespace)) {
        continue;
    }

    $componentNs = $getComponentNamespace($r);

    if (!$componentNs) {
        continue;
    }

    foreach ($class_uses_namespaces($r) as $u) {
        if (str_starts_with($u, $componentNs)) {
            continue;
        }

        $useNs = $getComponentNamespace(new ReflectionClass($u), $r);

        if (!$useNs || $useNs === $componentNs) {
            continue;
        }

        if (!in_array($useNs, $psrMap[$componentNs], true)) {
            echo sprintf('"%s" uses "%s" although "%s" is not one of its dependencies %s', $className, $u, $useNs, \PHP_EOL);
            $exitCode = 1;

            if ($stopOnFailure) {
                exit($exitCode);
            }
        }
    }
}

exit($exitCode);

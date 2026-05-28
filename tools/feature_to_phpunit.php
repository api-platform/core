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

$args = array_slice($argv, 1);
$setupHook = '';

if ($args && '--setup=' === substr($args[0], 0, 8)) {
    $setupHook = substr(array_shift($args), 8);
}

if (!$args) {
    fwrite(\STDERR, "usage: php {$argv[0]} [--setup=callable] <feature-file> [<feature-file>...]\n");
    exit(1);
}

foreach ($args as $featurePath) {
    if (!is_file($featurePath)) {
        fwrite(\STDERR, "missing: $featurePath\n");
        exit(2);
    }

    $src = file_get_contents($featurePath);
    $lines = preg_split('/\r?\n/', $src);

    $scenarios = [];
    $cur = null;
    $inBody = false;
    $body = '';
    $bodyTarget = 'json';

    $flush = static function () use (&$cur, &$scenarios): void {
        if (null !== $cur) {
            $scenarios[] = $cur;
        }
        $cur = null;
    };

    foreach ($lines as $line) {
        if (preg_match('/^\s*Scenario:\s*(.+)$/', $line, $m)) {
            $flush();
            $cur = ['title' => trim($m[1]), 'url' => null, 'httpMethod' => 'GET', 'status' => 200, 'json' => null, 'jsonMode' => null, 'requestBody' => null, 'contentType' => null, 'expectedContentType' => null];
            $inBody = false;
            $body = '';
            continue;
        }

        if (null === $cur) {
            continue;
        }

        if ($inBody) {
            if (preg_match('/^\s*"""\s*$/', $line)) {
                if ('requestBody' === $bodyTarget) {
                    $cur['requestBody'] = trim($body);
                } else {
                    $cur['json'] = trim($body);
                }
                $inBody = false;
                $body = '';
                $bodyTarget = 'json';
                continue;
            }
            $body .= $line."\n";
            continue;
        }

        if (preg_match('/I add "Content-Type" header equal to "([^"]+)"/', $line, $m)) {
            $cur['contentType'] = $m[1];
            continue;
        }

        if (preg_match('/the header "Content-Type" should be equal to "([^"]+)"/', $line, $m)) {
            $cur['expectedContentType'] = $m[1];
            continue;
        }

        if (preg_match('/I send a "([A-Z]+)" request to "([^"]+)"\s+with body:\s*$/', $line, $m)) {
            $cur['httpMethod'] = $m[1];
            $cur['url'] = $m[2];
            $bodyTarget = 'requestBody';
            continue;
        }

        if (preg_match('/I send a "([A-Z]+)" request to "([^"]+)"/', $line, $m)) {
            $cur['httpMethod'] = $m[1];
            $cur['url'] = $m[2];
            continue;
        }

        if (preg_match('/response status code should be (\d+)/', $line, $m)) {
            $cur['status'] = (int) $m[1];
            continue;
        }

        if (preg_match('/JSON should be equal to:\s*$/', $line)) {
            $cur['jsonMode'] = 'equals';
            continue;
        }

        if (preg_match('/JSON should be a superset of:\s*$/', $line)) {
            $cur['jsonMode'] = 'contains';
            continue;
        }

        if (preg_match('/JSON should be valid according to this schema:\s*$/', $line)) {
            $cur['jsonMode'] = 'schema';
            continue;
        }

        if (preg_match('/^\s*"""\s*$/', $line)) {
            $inBody = true;
            $body = '';
            continue;
        }
    }
    $flush();

    $used = [];
    foreach ($scenarios as $scenario) {
        $base = makeMethodName($scenario['title']);
        $name = $base;
        $i = 2;
        while (isset($used[$name])) {
            $name = $base.$i;
            ++$i;
        }
        $used[$name] = true;
        $scenario['method'] = $name;
        $scenario['setupHook'] = $setupHook;
        echo emitMethod($scenario);
    }
}

function emitMethod(array $s): string
{
    $method = $s['method'] ?? makeMethodName($s['title']);
    $url = $s['url'] ?? '';
    $status = $s['status'];
    $httpMethod = $s['httpMethod'] ?? 'GET';

    $out = "\n    public function {$method}(): void\n    {\n";
    if (!empty($s['setupHook'])) {
        $out .= "        \$this->{$s['setupHook']}();\n\n";
    } else {
        $out .= "        \$this->skipIfNotElasticsearch();\n";
        $out .= "        \$this->initializeElasticsearch();\n\n";
    }
    $headers = ['Accept' => 'application/ld+json'];
    if (!empty($s['contentType'])) {
        $headers['Content-Type'] = $s['contentType'];
    }
    $requestOptions = [];
    foreach ($headers as $k => $v) {
        $requestOptions['headers'][$k] = $v;
    }
    if (!empty($s['requestBody'])) {
        $requestOptions['body'] = $s['requestBody'];
    }
    $requestOptionsExport = var_export($requestOptions, true);
    $out .= "        \$response = self::createClient()->request('{$httpMethod}', ".var_export($url, true).", {$requestOptionsExport});\n\n";
    $out .= "        \$this->assertResponseStatusCodeSame({$status});\n";
    if (!empty($s['expectedContentType'])) {
        $out .= "        \$this->assertResponseHeaderSame('content-type', ".var_export($s['expectedContentType'], true).");\n";
    }

    if (null !== $s['json']) {
        $heredoc = "<<<'JSON'\n".$s['json']."\nJSON";
        if ('schema' === $s['jsonMode']) {
            $out .= "        \$this->assertMatchesJsonSchema({$heredoc});\n";
        } else {
            $assert = 'contains' === $s['jsonMode'] ? 'assertJsonContains' : 'assertJsonEquals';
            $out .= "        \$this->{$assert}({$heredoc});\n";
        }
    }

    $out .= "    }\n";

    return $out;
}

function makeMethodName(string $title): string
{
    $clean = preg_replace('/[^A-Za-z0-9]+/', ' ', $title);
    $words = array_filter(array_map('ucfirst', explode(' ', strtolower($clean))));

    return 'test'.implode('', $words);
}

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

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0\r\n",
    ],
];

$context = stream_context_create($opts);
$hydraContext = json_decode(file_get_contents('http://www.w3.org/ns/hydra/context.jsonld', false, $context), true);
$file = fopen('src/JsonLd/HydraContext.php', 'w');

$str = "<?php

declare(strict_types=1);

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\JsonLd;

const HYDRA_CONTEXT = ".var_export($hydraContext, true).';';

fwrite($file, $str);
fclose($file);

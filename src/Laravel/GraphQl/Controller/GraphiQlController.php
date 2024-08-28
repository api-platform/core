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

namespace ApiPlatform\Laravel\GraphQl\Controller;

use Illuminate\Http\Response;

readonly class GraphiQlController
{
    public function __construct(private readonly string $prefix)
    {
    }

    public function __invoke(): Response
    {
        return new Response(view('api-platform::graphiql', ['graphiql_data' => ['entrypoint' => $this->prefix.'/graphql']]), 200);
    }
}

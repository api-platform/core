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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\State\LoginProcessor;

/**
 * Fake login operation used to try the Swagger UI login plugin ("Authorize with this token" button).
 * The token returned by this operation is applied to the "Some_Authorization_Name" security scheme
 * defined in tests/Fixtures/app/config/config_swagger.php.
 */
#[ApiResource(
    shortName: 'Login',
    operations: [
        new Post(
            uriTemplate: '/login',
            status: 200,
            openapi: new Operation(
                summary: 'Fake login returning a token',
                // no explicit "security": the login plugin opts login operations out of the global security requirements
                extensionProperties: [
                    OpenApiFactory::API_PLATFORM_LOGIN => [
                        'securityScheme' => 'Some_Authorization_Name',
                        'tokenPath' => 'token',
                    ],
                ],
            ),
            processor: LoginProcessor::class,
        ),
    ],
)]
class LoginResource
{
    public ?string $username = null;

    public ?string $password = null;

    #[ApiProperty(writable: false)]
    public ?string $token = null;
}

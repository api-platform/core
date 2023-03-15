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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Controller\Common;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ControllerResource;
use Symfony\Component\Routing\Annotation\Route;

class ApiResourceController
{
    #[Route(path: '/api_resource_controller/{id}', format: 'jsonld')]
    public function index(#[ApiResource(provider: [self::class, 'getData'])] ControllerResource $controllerResource): ControllerResource
    {
        return $controllerResource;
    }

    #[Route(path: '/api_resource_controller_json/{id}', format: 'json')]
    public function indexJson(#[ApiResource(provider: [self::class, 'getData'])] ControllerResource $controllerResource): ControllerResource
    {
        return $controllerResource;
    }

    #[Route(methods: 'POST', path: '/api_resource_controller', format: 'jsonld')]
    public function create(#[ApiResource(processor: [self::class, 'process'])] ControllerResource $controllerResource): ControllerResource
    {
        return $controllerResource;
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): ControllerResource
    {
        $data = new ControllerResource();
        $data->id = $uriVariables['id'];
        $data->name = 'soyuka';

        return $data;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ControllerResource
    {
        return $data;
    }
}

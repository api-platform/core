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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/issue7939_foos/{fooId}/bars/{barId}/baz',
            uriVariables: [
                'fooId' => new Link(fromClass: Issue7939FooResource::class),
                'barId' => new Link(
                    fromClass: Issue7939BarResource::class,
                    identifiers: ['id'],
                    provider: ReadLinkParameterProvider::class,
                ),
            ],
            provider: [self::class, 'provide'],
        ),
        new Get(
            uriTemplate: '/issue7939_foos/{fooId}/bars/{barId}/baz_strict',
            uriVariables: [
                'fooId' => new Link(
                    fromClass: Issue7939FooResource::class,
                    provider: [self::class, 'validateParent'],
                ),
                'barId' => new Link(
                    fromClass: Issue7939BarResource::class,
                    identifiers: ['id'],
                    provider: ReadLinkParameterProvider::class,
                ),
            ],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class Issue7939BazResource
{
    public string $id = '1';
    public string $barId = '';
    public string $fooId = '';

    public static function provide(Operation $operation, array $uriVariables = [])
    {
        $r = new self();
        $r->fooId = (string) ($uriVariables['fooId'] ?? '');
        $r->barId = (string) ($uriVariables['barId'] ?? '');

        return $r;
    }

    public static function validateParent(Parameter $parameter, array $values = [], array $context = []): ?Operation
    {
        $barId = (string) ($values['barId'] ?? '');
        $fooId = (string) ($values['fooId'] ?? '');

        if (Issue7939BarResource::parentOf($barId) !== $fooId) {
            throw new NotFoundHttpException('Bar does not belong to the requested Foo.');
        }

        return $context['operation'] ?? null;
    }
}

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

namespace ApiPlatform\Tests\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Employee;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RespondProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testRedirectToOperation(): void
    {
        $canonicalUriTemplateRedirectingOperation = new Get(
            status: 302,
            extraProperties: [
                'canonical_uri_template' => '/canonical',
            ]
        );

        $alternateRedirectingResourceOperation = new Get(
            status: 308,
            extraProperties: [
                'is_alternate_resource_metadata' => true,
            ]
        );

        $alternateResourceOperation = new Get(
            extraProperties: [
                'is_alternate_resource_metadata' => true,
            ]
        );

        $operationMetadataFactory = $this->prophesize(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory
            ->create('/canonical', Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn(new Get(uriTemplate: '/canonical'));

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver
            ->isResourceClass(Employee::class)
            ->willReturn(true);

        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $iriConverter
            ->getIriFromResource(Argument::cetera())
            ->will(static function (array $args): string {
                return ($args[2] ?? null)?->getUriTemplate() ?? '/default';
            });

        /** @var ProcessorInterface<string, Response> $respondProcessor */
        $respondProcessor = new RespondProcessor($iriConverter->reveal(), $resourceClassResolver->reveal(), $operationMetadataFactory->reveal());

        $response = $respondProcessor->process('content', $canonicalUriTemplateRedirectingOperation, context: [
            'request' => new Request(),
            'original_data' => new Employee(),
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/canonical', $response->headers->get('Location'));

        $response = $respondProcessor->process('content', $alternateRedirectingResourceOperation, context: [
            'request' => new Request(),
            'original_data' => new Employee(),
        ]);

        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('/default', $response->headers->get('Location'));

        $response = $respondProcessor->process('content', $alternateResourceOperation, context: [
            'request' => new Request(),
            'original_data' => new Employee(),
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('Location'));
    }

    public function testAddsExceptionHeaders(): void
    {
        $operation = new Get();

        /** @var ProcessorInterface<string, Response> $respondProcessor */
        $respondProcessor = new RespondProcessor();
        $req = new Request();
        $req->attributes->set('exception', new TooManyRequestsHttpException(32));
        $response = $respondProcessor->process('content', new Get(), context: [
            'request' => $req,
        ]);

        $this->assertSame('32', $response->headers->get('retry-after'));
    }
}

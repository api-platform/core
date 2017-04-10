<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Swagger\Processor\SwaggerExtractorProcessor;
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationGenerator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable Swagger API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DocumentationNormalizer implements NormalizerInterface
{
    const SWAGGER_VERSION = '2.0';
    const FORMAT = 'json';

    private $urlGenerator;
    private $swaggerExtractorProcessor;
    private $swaggerDefinitions;
    private $swaggerOperationGenerator;
    private $oauthEnabled;
    private $oauthType;
    private $oauthFlow;
    private $oauthTokenUrl;
    private $oauthAuthorizationUrl;
    private $oauthScopes;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        SwaggerExtractorProcessor $swaggerExtractorProcessor,
        SwaggerDefinitions $swaggerDefinitions,
        SwaggerOperationGenerator $swaggerOperationGenerator,
        $oauthEnabled = false,
        $oauthType = '',
        $oauthFlow = '',
        $oauthTokenUrl = '',
        $oauthAuthorizationUrl = '',
        $oauthScopes = []
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->swaggerExtractorProcessor = $swaggerExtractorProcessor;
        $this->swaggerDefinitions = $swaggerDefinitions;
        $this->swaggerOperationGenerator = $swaggerOperationGenerator;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $operationsList = $this->swaggerOperationGenerator->generate($object);
        $paths = $this->swaggerExtractorProcessor->process($operationsList);
        $definitions = $this->swaggerDefinitions->getDefinitions();

        $definitions->ksort();
        $paths->ksort();

        return $this->computeDoc($object, $definitions, $paths);
    }

    /**
     * Computes the Swagger documentation.
     *
     * @param Documentation $documentation
     * @param \ArrayObject  $definitions
     * @param \ArrayObject  $paths
     *
     * @return array
     */
    private function computeDoc(Documentation $documentation, \ArrayObject $definitions, \ArrayObject $paths): array
    {
        $doc = [
            'swagger' => self::SWAGGER_VERSION,
            'basePath' => $this->urlGenerator->generate('api_entrypoint'),
            'info' => [
                'title' => $documentation->getTitle(),
                'version' => $documentation->getVersion(),
            ],
            'paths' => $paths,
        ];

        if ($this->oauthEnabled) {
            $doc['securityDefinitions'] = [
                'oauth' => [
                    'type' => $this->oauthType,
                    'description' => 'OAuth client_credentials Grant',
                    'flow' => $this->oauthFlow,
                    'tokenUrl' => $this->oauthTokenUrl,
                    'authorizationUrl' => $this->oauthAuthorizationUrl,
                    'scopes' => $this->oauthScopes,
                ],
            ];

            $doc['security'] = [['oauth' => []]];
        }

        if ('' !== $description = $documentation->getDescription()) {
            $doc['info']['description'] = $description;
        }

        if (count($definitions) > 0) {
            $doc['definitions'] = $definitions;
        }

        return $doc;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }
}

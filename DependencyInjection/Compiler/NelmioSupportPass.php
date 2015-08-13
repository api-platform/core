<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\DependencyInjection\Compiler;

use Dunglas\ApiBundle\Nelmio\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\DependencyInjection\RegisterExtractorParsersPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The role of this compiler pass is to unregister any api bundle stuff from the NelmioApiDoc bundle services.
 * NelmioApiDoc providers and parsers related to the api bundle are now registered by the api bundle itself.
 * This should be removed once a new major version of NelmioApiDoc is released and api bundle stuff removed from it.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class NelmioSupportPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('nelmio_api_doc.annotations_provider.dunglas_api_annotation_provider')) {
            return;
        }
        $container->removeDefinition('nelmio_api_doc.annotations_provider.dunglas_api_annotation_provider');
        $container->removeDefinition('nelmio_api_doc.parser.dunglas_api_parser');

        $apiDocExtractorDefinition = $container->getDefinition('nelmio_api_doc.extractor.api_doc_extractor');
        $apiDocExtractorDefinition->setClass(ApiDocExtractor::class);
        /** @var Reference[] $annotationsProviderReferences */
        $annotationsProviderReferences = $apiDocExtractorDefinition->getArgument(6);

        if (false !== $key = array_search('nelmio_api_doc.annotations_provider.dunglas_api_annotation_provider', $annotationsProviderReferences)) {
            unset($annotationsProviderReferences[$key]);
        }
        $apiDocExtractorDefinition->replaceArgument(6, $annotationsProviderReferences);

        while ($apiDocExtractorDefinition->hasMethodCall('addParser')) {
            // Remove all "addParser" method calls
            $apiDocExtractorDefinition->removeMethodCall('addParser');
        }
        // Re-execute the Nelmio extractor parsers pass in order to re-register "addParser" method calls.
        (new RegisterExtractorParsersPass())->process($container);
    }
}

<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Processor;

use ApiPlatform\Core\Swagger\Extractor\SwaggerOperationExtractorInterface;

final class SwaggerExtractorProcessor
{
    private $extractorList;

    /**
     * SwaggerExtractorProcessor constructor.
     *
     * @param SwaggerOperationExtractorInterface[] $extractorList
     */
    public function __construct(array $extractorList)
    {
        $this->extractorList = $extractorList;
    }

    public function process(array $operationList)
    {
        $result = new \ArrayObject([]);

        foreach ($operationList as $operation) {
            foreach ($this->extractorList as $extractor) {
                if ($extractor->supportsExtraction($operation)) {
                    $paths = $extractor->extract($operation);
                    $this->mergeDocumentation($paths, $result);
                }
            }
        }

        return $result;
    }

    /**
     * @param $paths
     * @param $result
     */
    private function mergeDocumentation(\ArrayObject $paths, \ArrayObject $result)
    {
        foreach ($paths as $path => $methods) {
            if (!isset($path, $result)) {
                $result[$path] = $methods;
                continue;
            }
            foreach ($methods as $method => $documentation) {
                if (!isset($result[$path][$method])) {
                    $result[$path][$method] = $documentation;
                    continue;
                }
                $mergedDocumentation = array_merge((array) $documentation, (array) $result[$path][$method]);
                $result[$path][$method] = new \ArrayObject($mergedDocumentation);
            }
        }
    }
}

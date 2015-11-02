<?php

namespace Dunglas\ApiBundle\Nelmio\Extractor;

use Doctrine\Common\Util\ClassUtils;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as BaseApiDocExtractor;
use Symfony\Component\HttpFoundation\Request;

class ApiDocExtractor extends BaseApiDocExtractor
{
    public function getReflectionMethod($controller)
    {
        $reflectionMethod = parent::getReflectionMethod($controller);
        if (null !== $reflectionMethod) {
            return $reflectionMethod;
        }

        if ($this->container->has($controller)) {
            $this->container->enterScope('request');
            $this->container->set('request', new Request(), 'request');
            $class = ClassUtils::getRealClass(get_class($this->container->get($controller)));
            $this->container->leaveScope('request');
            if (!isset($method) && method_exists($class, '__invoke')) {
                $method = '__invoke';
            }
        }

        if (isset($class) && isset($method)) {
            try {
                return new \ReflectionMethod($class, $method);
            } catch (\ReflectionException $e) {
            }
        }

        return;
    }
}

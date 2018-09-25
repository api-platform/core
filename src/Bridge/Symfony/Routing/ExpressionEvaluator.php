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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * {@inheritdoc}
 *
 * @author Vidy Videni <vidy.videni@gmail.com>
 */
class ExpressionEvaluator
{
    const EXPRESSION_REGEX = '/expr\((?P<expression>.+)\)/';

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

   /**
     * @var array
     */
    private $context;

    /**
     * @var array
     */
    private $cache = [];

    public function __construct(ExpressionLanguage $expressionLanguage, $context = [])
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->context = $context;
    }

    /**
     * @param  string $expression
     * @param  mixed  $data
     * @return mixed
     */
    public function evaluate($expression, $data)
    {
        if (!is_string($expression)) {
            return $expression;
        }

        $key = $expression;

        if (!array_key_exists($key, $this->cache)) {
            if (!preg_match(self::EXPRESSION_REGEX, $expression, $matches)) {
                $this->cache[$key] = false;
            } else {
                $expression = $matches['expression'];
                $context = $this->context;
                $context['object'] = $data;
                $this->cache[$key] = $this->expressionLanguage->parse($expression, array_keys($context));
            }
        }

        if (false !== $this->cache[$key]) {
            if (!isset($context)) {
                $context = $this->context;
                $context['object'] = $data;
            }

            return $this->expressionLanguage->evaluate($this->cache[$key], $context);
        }

        return $expression;
    }

    public function evaluateArray(array $array, $data)
    {
        $newArray = array();
        foreach ($array as $key => $value) {
            $key   = $this->evaluate($key, $data);
            $value = is_array($value) ? $this->evaluateArray($value, $data) : $this->evaluate($value, $data);

            $newArray[$key] = $value;
        }

        return $newArray;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setContextVariable($name, $value)
    {
        $this->context[$name] = $value;
    }
}

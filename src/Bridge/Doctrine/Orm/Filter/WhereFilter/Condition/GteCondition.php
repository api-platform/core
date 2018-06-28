<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition;

use Doctrine\ORM\Query\Expr;

class GteCondition implements ConditionInterface
{
    public const TYPE = 'gte';

    protected $key;

    protected $values;

    public function __construct(string $key, array $values)
    {
        $this->key = $key;
        $this->values = $values;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(Expr $expr)
    {
        $or = $expr->orX();
        foreach ($this->values as $value) {
            $or->add(
                $expr->gte($this->key, $expr->literal($value))
            );
        }

        return $or;
    }
}

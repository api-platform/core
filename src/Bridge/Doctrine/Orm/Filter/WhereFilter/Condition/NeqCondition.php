<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition;

use Doctrine\ORM\Query\Expr;

class NeqCondition implements ConditionInterface
{
    public const TYPE = 'neq';

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
                $expr->neq($this->key, $expr->literal($value))
            );
        }

        return $or;
    }
}

<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition;

use Doctrine\ORM\Query\Expr;

class OrCondition implements ConditionInterface
{
    public const TYPE = 'or';

    /**
     * @var ConditionInterface[]
     */
    protected $conditions;

    public function __construct(ConditionInterface ...$conditions)
    {
        $this->conditions = $conditions ?? [];
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(Expr $expr)
    {
        $or = $expr->orX();

        foreach ($this->conditions as $condition) {
            $or->add($condition->apply($expr));
        }

        return $or;
    }
}

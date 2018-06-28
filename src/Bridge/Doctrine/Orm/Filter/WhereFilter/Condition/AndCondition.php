<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition;

use Doctrine\ORM\Query\Expr;

class AndCondition implements ConditionInterface
{
    public const TYPE = 'and';

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
        $and = $expr->andX();

        foreach ($this->conditions as $condition) {
            $and->add($condition->apply($expr));
        }

        return $and;
    }
}

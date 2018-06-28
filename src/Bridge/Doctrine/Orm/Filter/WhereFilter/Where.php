<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\AndCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\ConditionInterface;
use Doctrine\ORM\QueryBuilder;

class Where
{
    /**
     * @var ConditionInterface[]
     */
    protected $conditions;

    public function __construct(?ConditionInterface ...$conditions)
    {
        $this->conditions = $conditions ?? [];
    }

    /**
     * @return ConditionInterface[]
     */
    public function getConditions(): array
    {
        return array_values($this->conditions);
    }

    public function addCondition(ConditionInterface $condition): void
    {
        if (isset($this->conditions[$condition->getType()])) {
            $currentCondition = $this->conditions[$condition->getType()];
            unset($this->conditions[$condition->getType()]);
            $condition = new AndCondition($currentCondition, $condition);
        }

        $this->conditions[$condition->getType()] = $condition;
    }

    public function apply(QueryBuilder $queryBuilder)
    {
        foreach ($this->conditions as $condition) {
            $expr = $queryBuilder->expr();
            $queryBuilder->andWhere($condition->apply($expr));
        }
    }
}

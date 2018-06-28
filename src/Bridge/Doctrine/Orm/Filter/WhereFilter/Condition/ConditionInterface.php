<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition;

use Doctrine\ORM\Query\Expr;

interface ConditionInterface
{
    public function getType(): string;

    public function apply(Expr $expr);
}

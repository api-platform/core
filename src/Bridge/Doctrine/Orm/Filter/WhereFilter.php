<?php

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Where;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\AndCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\ConditionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\EqCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\GtCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\GteCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\LtCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\LteCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\NeqCondition;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\WhereFilter\Condition\OrCondition;

class WhereFilter implements FilterInterface
{
    private const QUERY_STRING_WHERE = 'where';

    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        if (null === $whereData = $request->query->get(self::QUERY_STRING_WHERE)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $where = $this->buildWhereFromArray($rootAlias, $whereData);

        $where->apply($queryBuilder);
    }

    public function getDescription(string $resourceClass): array
    {
        return []; // TODO
    }

    public function buildWhereFromArray(string $alias, array $whereArray): Where
    {
        $where = new Where();
        foreach ($whereArray as $conditionType => $conditionArray) {
            $type = array_keys($conditionArray)[0];

            switch (true) {
                case $conditionType === AndCondition::TYPE:
                    $conditions = $this->buildWhereFromArray($alias, $conditionArray)->getConditions();
                    $where->addCondition(new AndCondition(...$conditions));
                    break;
                case $conditionType === OrCondition::TYPE:
                    $conditions = $this->buildWhereFromArray($alias, $conditionArray)->getConditions();
                    $where->addCondition(new OrCondition(...$conditions));
                    break;
                case $type === EqCondition::TYPE:
                    $where->addCondition(new EqCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case $type === NeqCondition::TYPE:
                    $where->addCondition(new NeqCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case $type === GtCondition::TYPE:
                    $where->addCondition(new GtCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case $type === GteCondition::TYPE:
                    $where->addCondition(new GteCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case $type === LtCondition::TYPE:
                    $where->addCondition(new LtCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
                case $type === LteCondition::TYPE:
                    $where->addCondition(new LteCondition($alias.'.'.$conditionType, (array) $conditionArray[$type]));
                    break;
            }
        }

        return $where;
    }
}

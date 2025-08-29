<?php declare(strict_types=1);
echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class <?php echo $class_name; ?> implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait; // Here for backward compatibility, keep it until 5.0.

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Retrieve the parameter and it's value
        // $parameter = $context['parameter'];
        // $value = $parameter->getValue();

        // Retrieve the property
        // $property = $parameter->getProperty();

        // Retrieve alias and parameter name
        // $alias = $queryBuilder->getRootAliases()[0];
        // $parameterName = $queryNameGenerator->generateParameterName($property);

        // TODO: make your awesome query using the $queryBuilder
        // $queryBuilder->
    }
}

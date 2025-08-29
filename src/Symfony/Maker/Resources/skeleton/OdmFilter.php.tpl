<?php declare(strict_types=1);
echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

class <?php echo $class_name; ?> implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait; // Here for backward compatibility, keep it until 5.0.

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        // Retrieve the parameter and it's value
        // $parameter = $context['parameter'];
        // $value = $parameter->getValue();

        // Retrieve the property
        // $property = $parameter->getProperty();

        // TODO: make your awesome query using the $aggregationBuilder
        // $aggregationBuilder->
    }
}

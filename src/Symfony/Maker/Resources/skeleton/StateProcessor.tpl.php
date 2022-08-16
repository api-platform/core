<?php declare(strict_types=1);
echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

class <?php echo $class_name; ?> implements ProcessorInterface
{
    public function process(object $data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        // Handle the state

        return $data;
    }
}

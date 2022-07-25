<?php declare(strict_types=1);
echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class <?php echo $class_name; ?> implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])<?php if (\PHP_VERSION_ID >= 80000) { ?>: object|array|null<?php } ?>

    {
        // Retrieve the state from somewhere
    }
}

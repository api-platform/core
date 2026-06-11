<?php declare(strict_types=1);
echo "<?php\n";
?>

namespace <?php echo $namespace; ?>;

<?php foreach ($operations as $op): ?>
use ApiPlatform\Metadata\<?php echo $op; ?>;
<?php endforeach; ?>
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class <?php echo $class_name; ?> implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
<?php foreach ($operations as $op): ?>
        if ($operation instanceof <?php echo $op; ?>) {
            // TODO: provide state for <?php echo $op; ?> operation
        }

<?php endforeach; ?>
<?php if (!$operations): ?>
        // Retrieve the state from somewhere
<?php endif; ?>
        return null;
    }
}

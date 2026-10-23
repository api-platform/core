<?php declare(strict_types=1);
echo "<?php\n";
?>

namespace <?php echo $namespace; ?>;

use ApiPlatform\Metadata\ApiResource;
<?php foreach ($operations as $op): ?>
use ApiPlatform\Metadata\<?php echo $op; ?>;
<?php endforeach; ?>
<?php if ($provider_class): ?>
use <?php echo $provider_class; ?>;
<?php endif; ?>
<?php if ($processor_class): ?>
use <?php echo $processor_class; ?>;
<?php endif; ?>
<?php if ($has_validator): ?>
use Symfony\Component\Validator\Constraints as Assert;
<?php endif; ?>

#[ApiResource(
<?php if ($operations): ?>
    operations: [
<?php foreach ($operations as $op): ?>
        new <?php echo $op; ?>(),
<?php endforeach; ?>
    ],
<?php endif; ?>
<?php if ($provider_class): ?>
    provider: <?php echo $provider_short; ?>::class,
<?php endif; ?>
<?php if ($processor_class): ?>
    processor: <?php echo $processor_short; ?>::class,
<?php endif; ?>
)]
class <?php echo $class_name."\n"; ?>
{
<?php foreach ($fields as $i => $field): ?>
<?php $type = $field['nullable'] ? '?'.$field['type'] : $field['type']; ?>
<?php if (in_array($field['name'], $validated_fields, true)): ?>
    #[Assert\NotBlank]
<?php endif; ?>
    public <?php echo $type; ?> $<?php echo $field['name']; ?><?php echo $field['nullable'] ? ' = null' : ''; ?>;
<?php if ($i < count($fields) - 1): ?>

<?php endif; ?>
<?php endforeach; ?>
}

<?php

$class = ($small ?? false) ? 'form-select form-select-sm' : 'form-select';
$req = ($required ?? false) ? ' required' : '';
$disabled = $options === [] ? ' disabled' : '';
?>
<select name="<?php echo htmlspecialchars($name); ?>" class="<?php echo $class; ?>" aria-label="Type de boisson"<?php echo $req . $disabled; ?><?php if (!empty($formId)): ?> form="<?php echo htmlspecialchars((string) $formId); ?>"<?php endif; ?>>
    <option value=""<?php echo $selected === '' ? ' selected' : ''; ?>>— Type —</option>
    <?php foreach ($options as $type): ?>
    <?php $isSelected = strcasecmp($selected, $type) === 0; ?>
    <option value="<?php echo htmlspecialchars($type); ?>"<?php echo $isSelected ? ' selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
    <?php endforeach; ?>
</select>

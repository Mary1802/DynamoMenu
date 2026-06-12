<?php

$class = ($small ?? false) ? 'form-select form-select-sm' : 'form-select';
$req = ($required ?? false) ? ' required' : '';
?>
<select name="<?php echo htmlspecialchars($name); ?>" class="<?php echo $class; ?>" aria-label="Catégorie"<?php echo $req; ?>>
    <option value=""<?php echo $selected === '' ? ' selected' : ''; ?>>— Catégorie —</option>
    <?php foreach ($options as $cat): ?>
    <?php $isSelected = strcasecmp($selected, $cat) === 0; ?>
    <option value="<?php echo htmlspecialchars($cat); ?>"<?php echo $isSelected ? ' selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
    <?php endforeach; ?>
</select>

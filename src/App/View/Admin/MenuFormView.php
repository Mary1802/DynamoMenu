<?php

declare(strict_types=1);

namespace App\View\Admin;

use App\View\View;

final class MenuFormView
{
    /** @param list<string> $categories */
    public static function platCategorySelect(
        string $name,
        string $selected,
        array $categories,
        bool $small = false,
        bool $required = false,
        ?string $formId = null
    ): void {
        $selected = trim($selected);
        $options = $categories;
        if ($selected !== '' && !in_array($selected, $options, true)) {
            $options[] = $selected;
            natcasesort($options);
            $options = array_values($options);
        }
        View::render('admin/plat-category-select', [
            'name' => $name,
            'selected' => $selected,
            'options' => $options,
            'small' => $small,
            'required' => $required,
            'formId' => $formId,
        ]);
    }

    /** @param list<string> $types */
    public static function boissonTypeSelect(
        string $name,
        string $selected,
        array $types,
        bool $small = false,
        bool $required = false,
        ?string $formId = null
    ): void {
        $selected = trim($selected);
        $options = $types;
        if ($selected !== '' && !in_array($selected, $options, true)) {
            $options[] = $selected;
            natcasesort($options);
            $options = array_values($options);
        }
        View::render('admin/boisson-type-select', [
            'name' => $name,
            'selected' => $selected,
            'options' => $options,
            'small' => $small,
            'required' => $required,
            'formId' => $formId,
        ]);
    }
}

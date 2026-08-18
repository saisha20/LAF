<?php
/**
 * category_icon.php
 * Returns a fallback emoji icon for a report card when no photo was
 * uploaded, based on its category name.
 */
function category_icon($categoryName) {
    $map = [
        'Electronics'            => '&#128187;',
        'Documents / ID Cards'   => '&#128179;',
        'Bags'                   => '&#127890;',
        'Clothing'                => '&#128085;',
        'Accessories'            => '&#128092;',
        'Keys'                    => '&#128273;',
        'Books & Stationery'     => '&#128218;',
        'Other'                   => '&#128230;',
    ];
    return $map[$categoryName] ?? '&#128230;';
}

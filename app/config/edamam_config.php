<?php
// Edamam Recipe Search API v2
// https://developer.edamam.com/edamam-recipe-api

define('EDAMAM_APP_ID',  '652ef1ae');
define('EDAMAM_APP_KEY', 'e045d4cc2055007333d8c6ee6ebe66a0');
define('EDAMAM_API_URL', 'https://api.edamam.com/api/recipes/v2');

// Cuántas recetas pedir por categoría (max 20 en plan free)
define('EDAMAM_MAX_PER_CATEGORY', 4);

// Días antes de eliminar recetas auto antiguas
define('EDAMAM_EXPIRE_DAYS', 60);

// Secret para el cron
define('EDAMAM_CRON_SECRET', 'bieniestar_recetas_2026_e9r3k');

// Categorías a buscar: categoria_bd => query Edamam
define('EDAMAM_CATEGORIES', [
    'desayuno' => 'healthy breakfast',
    'almuerzo' => 'light lunch brunch',
    'comida'   => 'healthy lunch meal',
    'merienda' => 'afternoon snack light',
    'cena'     => 'healthy dinner',
    'snack'    => 'healthy snack',
    'postre'   => 'healthy dessert',
]);

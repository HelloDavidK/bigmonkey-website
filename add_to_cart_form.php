<?php
declare(strict_types=1);

/**
 * Fichier d'inclusion pour compatibilité.
 *
 * Plusieurs pages incluent add_to_cart_form.php.
 * On expose ici de petites aides réutilisables pour les formulaires
 * d'ajout au panier sans imposer de rendu HTML global.
 */

if (!function_exists('sanitizePositiveInt')) {
    /**
     * @param mixed $value
     */
    function sanitizePositiveInt($value, int $default = 1): int
    {
        $filtered = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($filtered === false || $filtered === null) {
            return max(0, $default);
        }

        return (int) $filtered;
    }
}
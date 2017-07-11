<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types=1);

function status_icon($state) {
    switch ($state) {
        case 'failure':
            return 'remove-sign text-danger';
        case 'pending':
            return 'question-sign text-warning';
        case 'success':
            return 'ok-sign text-success';
    }
}

/**
 * Return the font color based on the background:
 * #333026 (github color) for high luminosity contrast
 * white for low luminosity contrast
 */
function font_color($hex) {
    $lc = luminosity_contrast(hex_to_rgb($hex), [51, 48, 38]);

    return $lc > 5 ? '#333026' : '#fff';
}

/**
 * Calculate the luminosity contrast between two colors
 * based on https://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php#luminosity_contrast
 */
function luminosity_contrast(array $foreground, array $background) {
    list($R1, $G1, $B1) = $foreground;
    list($R2, $G2, $B2) = $background;

    $L1 = 0.2126 * pow($R1 / 255, 2.2) +
        0.7152 * pow($G1 / 255, 2.2) +
        0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2 / 255, 2.2) +
        0.7152 * pow($G2 / 255, 2.2) +
        0.0722 * pow($B2 / 255, 2.2);

    if ($L1 > $L2) {
        return ($L1 + 0.05) / ($L2 + 0.05);
    } else {
        return ($L2 + 0.05) / ($L1 + 0.05);
    }
}

/**
 * Transform an hexadecimal color to RGB
 */
function hex_to_rgb($hex) {
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    return [$r, $g, $b];
}

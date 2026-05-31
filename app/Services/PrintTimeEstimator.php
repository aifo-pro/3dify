<?php

namespace App\Services;

use App\Models\Product;

/**
 * Rough print time estimator based on bounding-box dimensions.
 * Uses a simplified volumetric model — not a slicer, just a UX hint.
 */
class PrintTimeEstimator
{
    private const LAYER_HEIGHT_MM   = 0.2;
    private const PRINT_SPEED_MM_S  = 60;   // ~60 mm/s average
    private const TRAVEL_OVERHEAD   = 1.4;  // 40% overhead for travel/retract
    private const INFILL_PERCENT    = 0.15;

    /**
     * Returns estimated print time as a human-readable string,
     * or null if not enough dimension data.
     */
    public function estimate(Product $product): ?string
    {
        $x = (float) ($product->dim_x ?? 0);
        $y = (float) ($product->dim_y ?? 0);
        $z = (float) ($product->dim_z ?? 0);

        if ($x <= 0 || $y <= 0 || $z <= 0) {
            return null;
        }

        // Bounding-box volume in mm³
        $volume = $x * $y * $z;

        // Estimated solid volume with infill + perimeters (~30% of bounding box)
        $solidVolume = $volume * 0.30 * self::INFILL_PERCENT
            + $volume * 0.05; // perimeter shell approximation

        // Filament cross-section area (0.4mm nozzle, 0.2 layer ≈ 0.073 mm²)
        $filamentArea = 0.4 * self::LAYER_HEIGHT_MM;

        // Total extrusion length in mm
        $extrusionLength = $solidVolume / $filamentArea;

        // Print time in seconds
        $seconds = ($extrusionLength / self::PRINT_SPEED_MM_S) * self::TRAVEL_OVERHEAD;

        // Add layer changes: z/0.2 layers × ~0.5s each
        $layers = $z / self::LAYER_HEIGHT_MM;
        $seconds += $layers * 0.5;

        // Minimum 5 min, maximum 96h
        $seconds = max(300, min($seconds, 345600));

        return $this->format((int) $seconds);
    }

    private function format(int $seconds): string
    {
        $hours   = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);

        if ($hours >= 24) {
            $days = (int) floor($hours / 24);
            $h    = $hours % 24;
            return $days.'д'.($h > 0 ? ' '.$h.'г' : '');
        }

        if ($hours === 0) {
            return $minutes.' хв';
        }

        return $hours.'г'.($minutes > 0 ? ' '.$minutes.'хв' : '');
    }
}

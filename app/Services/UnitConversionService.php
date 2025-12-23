<?php

namespace App\Services;

use App\Models\Unit;

class UnitConversionService
{
    protected $units;

    public function __construct()
    {
        $this->loadUnits();
    }

    /**
     * Load all units into memory indexed by ID
     */
    public function loadUnits()
    {
        $this->units = Unit::all()->keyBy('id');
    }

    /**
     * Convert quantity from one unit to another
     */
    public function convert($quantity, $fromUnitId, $toUnitId): float
    {
        if ($fromUnitId == $toUnitId) {
            return $quantity;
        }

        $fromUnit = $this->units->get($fromUnitId);
        $toUnit = $this->units->get($toUnitId);

        if (!$fromUnit || !$toUnit) {
            return $quantity;
        }

        // Get base units
        $fromBase = $this->getBaseUnit($fromUnit);
        $toBase = $this->getBaseUnit($toUnit);

        // If base units are different, cannot convert
        if ($fromBase->id !== $toBase->id) {
            return $quantity;
        }

        // 1. Convert to base unit
        $baseQuantity = $this->convertToBase($quantity, $fromUnit);

        // 2. Convert from base to target unit
        return $this->convertFromBase($baseQuantity, $toUnit);
    }

    protected function getBaseUnit($unit)
    {
        if (!$unit->base_unit_id) {
            return $unit;
        }

        $parent = $this->units->get($unit->base_unit_id);
        if (!$parent) {
            return $unit; // Validation fallback
        }

        return $this->getBaseUnit($parent);
    }

    protected function convertToBase($quantity, $unit)
    {
        if (!$unit->base_unit_id) {
            return $quantity;
        }

        // Parent value = quantity * conversion_rate
        // e.g. 1 KG (Rate 1000) -> 1 * 1000 = 1000 Grams
        $converted = $quantity * ($unit->conversion_rate ?? 1);

        $parent = $this->units->get($unit->base_unit_id);
        return $this->convertToBase($converted, $parent);
    }

    protected function convertFromBase($quantity, $unit)
    {
        if (!$unit->base_unit_id) {
            return $quantity;
        }

        $parent = $this->units->get($unit->base_unit_id);
        $quantityInParent = $this->convertFromBase($quantity, $parent);

        // Child value = quantityInParent / conversion_rate
        // e.g. 1000 Grams -> 1000 / 1000 = 1 KG
        return $quantityInParent / ($unit->conversion_rate ?? 1);
    }
}

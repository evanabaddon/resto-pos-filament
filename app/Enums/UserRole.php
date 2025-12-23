<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Accountant = 'accountant';
    case Inventory = 'inventory';
    case Kitchen = 'kitchen';
    case Cashier = 'cashier';
    case Waiter = 'waiter';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin Operasional',
            self::Accountant => 'Staff Keuangan',
            self::Inventory => 'Staff Gudang',
            self::Kitchen => 'Kitchen / Dapur',
            self::Cashier => 'Cashier',
            self::Waiter => 'Waiter',
        };
    }

    public function canDelete(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => true,
            default => false,
        };
    }
}

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
            self::SuperAdmin => __('messages.role_super_admin'),
            self::Admin => __('messages.role_admin'),
            self::Accountant => __('messages.role_accountant'),
            self::Inventory => __('messages.role_inventory'),
            self::Kitchen => __('messages.role_kitchen'),
            self::Cashier => __('messages.role_cashier'),
            self::Waiter => __('messages.role_waiter'),
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

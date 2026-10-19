<?php

namespace App\Enums;

/**
 * Admin: manages users and sees all records. User: keeps his own collection.
 * Seller: only sees the records assigned to him and can register interest in them.
 */
enum Role: string
{
    case Admin = 'admin';
    case User = 'user';
    case Seller = 'seller';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::User => __('Benutzer (Rolle)'),
            self::Seller => __('Verkäufer'),
        };
    }
}

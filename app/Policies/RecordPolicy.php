<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;

/**
 * Admins may do everything, users only with their own records. Sellers may see the records assigned to them
 * and all records marked for selling, and register interest in them.
 */
class RecordPolicy
{
    public function view(User $user, Record $record): bool
    {
        return $user->isAdmin()
            || $record->user_id === $user->id
            || ($user->isSeller() && ($this->isOffered($record) || $this->isAssigned($user, $record)));
    }

    public function create(User $user): bool
    {
        return $user->managesCollection();
    }

    /**
     * Edit, delete, assign sellers and sell.
     */
    public function update(User $user, Record $record): bool
    {
        return $user->isAdmin() || ($user->managesCollection() && $record->user_id === $user->id);
    }

    public function delete(User $user, Record $record): bool
    {
        return $this->update($user, $record);
    }

    public function interest(User $user, Record $record): bool
    {
        return $user->isSeller() && ! $record->sold && ($this->isOffered($record) || $this->isAssigned($user, $record));
    }

    /**
     * Marked for selling: visible to all sellers.
     */
    private function isOffered(Record $record): bool
    {
        return $record->selling && ! $record->sold;
    }

    private function isAssigned(User $user, Record $record): bool
    {
        return $record->sellers()->whereKey($user->id)->exists();
    }
}

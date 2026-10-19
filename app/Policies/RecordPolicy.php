<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;

/**
 * Admins may do everything, users only with their own records. Sellers may see the records assigned to them
 * and all records marked for selling (or all records that are not sold, when the admin allows it), and register interest in them.
 */
class RecordPolicy
{
    public function view(User $user, Record $record): bool
    {
        return $user->isAdmin()
            || $record->user_id === $user->id
            || ($user->isSeller() && ($this->isOffered($user, $record) || $this->isAssigned($user, $record)));
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
        return $user->isSeller() && ! $record->sold && ($this->isOffered($user, $record) || $this->isAssigned($user, $record));
    }

    /**
     * Marked for selling: visible to all sellers. Sellers who see all records see every record that is not sold.
     */
    private function isOffered(User $user, Record $record): bool
    {
        return ! $record->sold && ($record->selling || $user->seesAllRecords());
    }

    private function isAssigned(User $user, Record $record): bool
    {
        return $record->sellers()->whereKey($user->id)->exists();
    }
}

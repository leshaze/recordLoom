<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;

/**
 * Admins may do everything, users only with their own records. Sellers may only see the records
 * assigned to them and register interest in them.
 */
class RecordPolicy
{
    public function view(User $user, Record $record): bool
    {
        return $user->isAdmin()
            || $record->user_id === $user->id
            || ($user->isSeller() && $this->isAssigned($user, $record));
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
        return $user->isSeller() && ! $record->sold && $this->isAssigned($user, $record);
    }

    private function isAssigned(User $user, Record $record): bool
    {
        return $record->sellers()->whereKey($user->id)->exists();
    }
}

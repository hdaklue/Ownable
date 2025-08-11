<?php

namespace Sowailem\Ownable\Traits;

use Sowailem\Ownable\Models\Ownership;

/**
 * Trait for models that can own other entities.
 * 
 * This trait provides functionality for models to own other models,
 * including ownership management, transfer, and querying capabilities.
 */
trait HasOwnables
{
    /**
     * Get all ownership records where this model is the owner.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function possessions()
    {
        return $this->morphMany(Ownership::class, 'owner');
    }

    /**
     * Get all ownable entities owned by this owner.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function ownables()
    {
        return $this->morphToMany(
            config('ownable.ownable_model', 'App\Models\Model'),
            'owner',
            'ownerships',
            'owner_id',
            'ownable_id'
        )->withPivot('is_current')->withTimestamps();
    }

    /**
     * Check if this owner owns the given ownable entity.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity to check
     * @return bool True if this owner owns the entity, false otherwise
     */
    public function owns($ownable)
    {
        return $this->ownables()
            ->where('ownable_id', $ownable->getKey())
            ->where('ownable_type', get_class($ownable))
            ->where('is_current', true)
            ->exists();
    }

    /**
     * Give ownership of an ownable entity to this owner.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @return bool True if ownership was given successfully, false if already owned
     */
    public function giveOwnershipTo($ownable)
    {
        if ($this->owns($ownable)) {
            return false;
        }

        $ownable->ownedBy($this);
        return true;
    }

    /**
     * Take ownership away from an ownable entity.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @return bool True if ownership was taken successfully, false if not owned
     */
    public function takeOwnershipFrom($ownable)
    {
        if (!$this->owns($ownable)) {
            return false;
        }

        $ownable->ownerships()
            ->where('owner_id', $this->getKey())
            ->where('owner_type', get_class($this))
            ->delete();

        return true;
    }

    /**
     * Transfer ownership of an ownable entity to a new owner.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @param \Illuminate\Database\Eloquent\Model $newOwner The new owner
     * @return bool True if transfer was successful, false if not owned by this owner
     */
    public function transferOwnership($ownable, $newOwner)
    {
        if (!$this->owns($ownable)) {
            return false;
        }

        $ownable->transferOwnershipTo($newOwner);
        return true;
    }
}
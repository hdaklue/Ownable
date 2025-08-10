<?php

namespace Sowailem\Ownable\Contracts;

/**
 * Interface for entities that can own other models.
 * 
 * This interface defines the contract for models that can have ownership
 * of other entities in the system.
 */
interface Owner
{
    /**
     * Get all ownable entities owned by this owner.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function ownables();

    /**
     * Check if this owner owns the given ownable entity.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity to check
     * @return bool True if this owner owns the entity, false otherwise
     */
    public function owns($ownable);

    /**
     * Give ownership of an ownable entity to this owner.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @return bool True if ownership was given successfully, false if already owned
     */
    public function giveOwnershipTo($ownable);

    /**
     * Take ownership away from an ownable entity.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @return bool True if ownership was taken successfully, false if not owned
     */
    public function takeOwnershipFrom($ownable);

    /**
     * Transfer ownership of an ownable entity to a new owner.
     * 
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @param \Illuminate\Database\Eloquent\Model $newOwner The new owner
     * @return bool True if transfer was successful, false if not owned by this owner
     */
    public function transferOwnership($ownable, $newOwner);
}
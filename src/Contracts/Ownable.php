<?php

namespace Sowailem\Ownable\Contracts;

/**
 * Interface for entities that can be owned by other models.
 * 
 * This interface defines the contract for models that can have ownership
 * relationships with other models in the system.
 */
interface Ownable
{
    /**
     * Get all owners of this ownable entity.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function owners();

    /**
     * Assign ownership of this entity to the given owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model
     * @return $this
     * @throws \Exception When the model or owner is not saved
     */
    public function ownedBy($owner);

    /**
     * Check if this entity is owned by the given owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model to check
     * @return bool True if owned by the given owner, false otherwise
     */
    public function isOwnedBy($owner);

    /**
     * Transfer ownership of this entity to a new owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $newOwner The new owner model
     * @return $this
     */
    public function transferOwnershipTo($newOwner);
}
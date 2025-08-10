<?php

namespace Sowailem\Ownable;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Sowailem\Ownable\Contracts\Ownable;

/**
 * Main Owner service class for managing ownership relationships.
 * 
 * This class provides a centralized service for managing ownership
 * operations between models, including giving ownership, transferring
 * ownership, and checking ownership status.
 */
class Owner
{
    /**
     * Give ownership of an ownable entity to an owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @return mixed The ownable entity with updated ownership
     * @throws \InvalidArgumentException When owner is not an Eloquent model or ownable doesn't implement Ownable contract
     */
    public function give($owner, $ownable)
    {
        if (!($owner instanceof Model)) {
            throw new \InvalidArgumentException('Owner must be an Eloquent model');
        }

        if (!($ownable instanceof Ownable)) {
            throw new \InvalidArgumentException('Ownable must implement Sowailem\Ownable\Contracts\Ownable');
        }

        return $ownable->ownedBy($owner);
    }

    /**
     * Transfer ownership of an ownable entity from one owner to another.
     * 
     * @param \Illuminate\Database\Eloquent\Model $fromOwner The current owner model
     * @param \Illuminate\Database\Eloquent\Model $toOwner The new owner model
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity
     * @return mixed The ownable entity with transferred ownership
     * @throws \InvalidArgumentException When owners are not Eloquent models or ownable doesn't implement Ownable contract
     */
    public function transfer($fromOwner, $toOwner, $ownable)
    {
        if (!($fromOwner instanceof Model) || !($toOwner instanceof Model)) {
            throw new InvalidArgumentException('Owners must be Eloquent models');
        }

        if (!($ownable instanceof Ownable)) {
            throw new InvalidArgumentException('Ownable must implement Sowailem\Ownable\Contracts\Ownable');
        }

        return $ownable->transferOwnershipTo($toOwner);
    }

    /**
     * Check if an owner owns a specific ownable entity.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model to check
     * @param \Sowailem\Ownable\Contracts\Ownable $ownable The ownable entity to check
     * @return bool True if the owner owns the entity, false otherwise
     * @throws \InvalidArgumentException When owner is not an Eloquent model or ownable doesn't implement Ownable contract
     */
    public function check($owner, $ownable)
    {
        if (!($owner instanceof Model)) {
            throw new InvalidArgumentException('Owner must be an Eloquent model');
        }

        if (!($ownable instanceof Ownable)) {
            throw new InvalidArgumentException('Ownable must implement Sowailem\Ownable\Contracts\Ownable');
        }

        return $ownable->isOwnedBy($owner);
    }
}
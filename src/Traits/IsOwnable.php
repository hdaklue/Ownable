<?php

namespace Sowailem\Ownable\Traits;

use Exception;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Sowailem\Ownable\Models\Ownership;

/**
 * Trait for models that can be owned by other entities.
 * 
 * This trait provides functionality for models to be owned by other models,
 * including ownership assignment, transfer, and querying capabilities.
 */
trait IsOwnable
{
    /**
     * Boot the IsOwnable trait.
     * 
     * Automatically deletes ownership records when the ownable model is deleted.
     * 
     * @return void
     */
    public static function bootIsOwnable(): void
    {
        static::deleting(function ($model) {
            $model->ownerships()->delete();
        });
    }

    /**
     * Get all ownership records for this ownable entity.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function ownerships(): MorphMany
    {
        return $this->morphMany(Ownership::class, 'ownable');
    }

    /**
     * Get all owners of this ownable entity.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function owners(): MorphToMany
    {
        return $this->morphToMany(
            config('ownable.owner_model', 'App\Models\User'),
            'ownable',
            'ownerships',
            'ownable_id',
            'owner_id'
        )->withPivot('is_current')->withTimestamps();
    }

    /**
     * Get the current owner of this ownable entity.
     * 
     * @return \Illuminate\Database\Eloquent\Model|null The current owner or null if no current owner
     */
    public function currentOwner()
    {
        return $this->owners()->wherePivot('is_current', true)->first();
    }

    /**
     * Assign ownership of this entity to the given owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model
     * @return $this
     * @throws \Exception When the model or owner is not saved
     */
    public function ownedBy($owner)
    {
        if (!$this->getKey()) {
            throw new Exception('Cannot assign ownership to unsaved model');
        }

        if (!$owner->getKey()) {
            throw new Exception('Cannot assign ownership from unsaved owner model');
        }

        if ($currentOwner = $this->currentOwner()) {
            $this->ownerships()
                ->where('owner_id', $currentOwner->getKey())
                ->where('owner_type', get_class($currentOwner))
                ->update(['is_current' => false]);
        }

        $this->owners()->attach($owner->getKey(), [
            'owner_type' => get_class($owner),
            'is_current' => true,
        ]);

        return $this;
    }

    /**
     * Check if this entity is owned by the given owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model to check
     * @return bool True if owned by the given owner, false otherwise
     */
    public function isOwnedBy($owner)
    {
        return $this->owners()
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', get_class($owner))
            ->where('is_current', true)
            ->exists();
    }

    /**
     * Transfer ownership of this entity to a new owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $newOwner The new owner model
     * @return $this
     */
    public function transferOwnershipTo($newOwner)
    {
        return $this->ownedBy($newOwner);
    }
}
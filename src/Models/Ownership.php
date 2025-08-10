<?php

namespace Sowailem\Ownable\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ownership model representing the relationship between owners and ownable entities.
 * 
 * This model stores the polymorphic many-to-many relationship data between
 * entities that can own and entities that can be owned.
 * 
 * @property int $id
 * @property int $owner_id The ID of the owner model
 * @property string $owner_type The class name of the owner model
 * @property int $ownable_id The ID of the ownable model
 * @property string $ownable_type The class name of the ownable model
 * @property bool $is_current Whether this is the current ownership relationship
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Ownership extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'ownerships';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<string>
     */
    protected $fillable = [
        'owner_id',
        'owner_type',
        'ownable_id',
        'ownable_type',
        'is_current',
    ];

    /**
     * The attributes that should be cast.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_current' => 'boolean',
    ];

    /**
     * Get the owner model that owns the ownable entity.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * Get the ownable entity that is owned.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function ownable()
    {
        return $this->morphTo();
    }
}
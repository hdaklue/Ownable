<?php

namespace Sowailem\Ownable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Sowailem\Ownable\Traits\HasOwnables;

class User extends Model
{
    use HasOwnables;

    protected $fillable = [
        'name',
        'email',
    ];
}
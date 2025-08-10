<?php

namespace Sowailem\Ownable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Sowailem\Ownable\Contracts\Ownable;
use Sowailem\Ownable\Traits\IsOwnable;

class Post extends Model implements Ownable
{
    use IsOwnable;

    protected $fillable = [
        'title',
        'content',
    ];
}
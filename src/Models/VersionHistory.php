<?php

namespace Sado729\ProjectVersion\Models;

use Illuminate\Database\Eloquent\Model;

class VersionHistory extends Model
{
    public $timestamps = false;
    public $table = 'version_histories';
    protected $fillable = ['version', 'commit_hash', 'recorded_at'];
    protected $casts = [
        'recorded_at' => 'datetime',
    ];
}

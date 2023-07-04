<?php

namespace Sado729\ProjectVersion\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class Information extends Model
{
    public $timestamps = false;
    protected $fillable = ['version'];
}
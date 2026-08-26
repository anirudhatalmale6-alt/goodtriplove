<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SecurityLog extends Model { protected $fillable=['user_id','event','severity','ip_address','user_agent','success','metadata']; protected $casts=['success'=>'boolean','metadata'=>'array']; }

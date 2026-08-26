<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmailVerificationCode extends Model { protected $fillable=['user_id','code_hash','attempts','expires_at','verified_at']; protected $casts=['expires_at'=>'datetime','verified_at'=>'datetime']; }

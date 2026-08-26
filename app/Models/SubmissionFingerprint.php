<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubmissionFingerprint extends Model { protected $fillable=['user_id','type','fingerprint','expires_at']; protected $casts=['expires_at'=>'datetime']; }

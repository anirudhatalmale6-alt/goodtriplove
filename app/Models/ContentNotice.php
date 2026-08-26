<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContentNotice extends Model {
 protected $fillable=['reporter_user_id','reporter_email','target_type','target_id','target_url','reason',
 'explanation','evidence','status','decision','decision_reason','reviewed_by','reviewed_at','notified_at'];
 protected $casts=['evidence'=>'array','reviewed_at'=>'datetime','notified_at'=>'datetime'];
}
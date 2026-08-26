<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CookieConsent extends Model {
 protected $fillable=['user_id','consent_key','policy_version','choices','ip_address','consented_at','withdrawn_at'];
 protected $casts=['choices'=>'array','consented_at'=>'datetime','withdrawn_at'=>'datetime'];
}
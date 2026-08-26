<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LegalAcceptance extends Model {
 protected $fillable=['user_id','document_key','version','locale','ip_address','user_agent','accepted_at'];
 protected $casts=['accepted_at'=>'datetime'];
}
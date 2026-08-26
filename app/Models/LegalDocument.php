<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LegalDocument extends Model {
 protected $fillable=['key','locale','version','title','content','published','published_at','updated_by'];
 protected $casts=['published'=>'boolean','published_at'=>'datetime'];
}
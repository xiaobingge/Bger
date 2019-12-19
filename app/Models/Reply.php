<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Reply extends Model{

    protected $table = "reply";

    protected $fillable = ['rule_id','type','content','media_id'];

}
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Rules extends Model{

    protected $table = "rules";

    protected $fillable = ['name','type','keyword','qr_code','match','reply_mode'];

}
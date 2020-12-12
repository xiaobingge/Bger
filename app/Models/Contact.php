<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model{

    protected $table = "contact";


    protected $fillable = [
        'uid','community_id','tag_id','phone'
    ];

}
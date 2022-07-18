<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    public $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ["id","price","quantity"];

}

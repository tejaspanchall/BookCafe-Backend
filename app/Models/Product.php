<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'dmi_products';
    
    protected $fillable = [
        'name',
        'category',
        'price'
    ];
} 
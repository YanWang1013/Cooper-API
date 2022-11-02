<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTypes extends Model
{
    //

    protected $table="t_service_type";

    protected $fillable = [
        'name',
        'driver_name',
        'image',
        'price',
        'fixed',
        'description',
        'status',
        'minute',
        'hour',
        'distance',
        'calculator',
        'capacity'
    ];

    public $timestamps=true;

}

<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DriverDocument extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table="t_driver_document";

    protected $fillable = [
        'driver_id',
        'document_id',
        'url',
        'expires_at',
        'unique_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];

    /**
     * The services that belong to the user.
     */
    public function driver()
    {
        return $this->belongsTo('App\Http\Models\Drivers');
    }
    /**
     * The driver document that belong to the document.
     */
    public function document()
    {
        return $this->belongsTo('App\Http\Models\DocType');
    }
}

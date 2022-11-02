<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DocType extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table="t_doc_type";

    protected $fillable = [
        'name',
        'type'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVehicle($query)
    {
        return $query->where('type', 'VEHICLE');
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDriver($query)
    {
        return $query->where('type', 'DRIVER');
    }

    /**
     * The doc type has many documents.
     */
    public function documents()
    {
        return $this->hasMany('App\Http\Models\DriverDocument', 'document_id');
    }

}

<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Cards extends Model
{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $table="t_cards";

    protected $fillable =   [   'email',
                                'brand',
                                'card_no',
                                'expiry_month',
                                'expiry_year',
                                'cvc',
                                'is_default'
                            ];
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    public $timestamps=true;
}

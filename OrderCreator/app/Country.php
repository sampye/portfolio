<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public function vats()
    {
        return $this->belongsToMany('App\Vat', 'country_vat')->withPivot('amount');
    }
}

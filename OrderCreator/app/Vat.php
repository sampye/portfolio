<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vat extends Model
{
    public function countries()
    {
        return $this->belongsToMany('App\Country', 'country_vat');
    }
}

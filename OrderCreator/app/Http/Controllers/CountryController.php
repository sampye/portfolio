<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Country;
use App\Vat;

class CountryController extends Controller
{
    public function getIndex()
    {
        $countries = Country::all();
        $vats = Vat::all();
        return view('content/countries')
            ->with('countries', $countries)
            ->with('vats', $vats);
    }
}

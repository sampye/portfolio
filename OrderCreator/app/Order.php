<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Order extends Model
{
    public function items()
    {
        return $this->belongsToMany('App\Item', 'order_item')->withPivot('quantity', 'vat');
    }

    public function calculateTotal($items, $country, $orderId)
    {
        $totalSum = 0.0;
        $country = Country::find($country);
        $orderItemsArray = array();

        foreach ($items as $key=>$value) {


            $item = Item::find($key);
            // Creating OrderItems while going through the calculations
            $orderItemsArray[] = array(
                'order_id' => $orderId,
                'item_id' => $item->id,
                'quantity' => $value,
                'vat' => ($country->vats[$item->vat_id-1]->pivot->amount == 0) ? 0 : ($item->price * ($country->vats[$item->vat_id-1]->pivot->amount/100)) * $value
            );
            $totalSum += ($item->price * (1+($country->vats[$item->vat_id-1]->pivot->amount/100))) * $value;
        }

        DB::table('order_item')->insert($orderItemsArray);

        return $totalSum;
    }
}

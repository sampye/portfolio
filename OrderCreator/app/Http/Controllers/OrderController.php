<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\Item;
use App\Country;
use Config;
use PDF;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Constraint\Count;

class OrderController extends Controller
{

    public function getIndex()
    {
        $orders = Order::orderBy('id', 'DESC')->get();
        return view('order/index')->with('orders', $orders);
    }

    public function getShow(Order $order)
    {
        $country = Country::find($order->country_id);
        return view('order/show')->with('order', $order)->with('country', $country);
    }

    public function getCreate()
    {
        $countries = Country::all();
        $items = Item::all();
        return view('order/create')->with('countries', $countries)
            ->with('items', $items);
    }

    public function postCreate(Request $request)
    {
        $inputs = $request->all();
        $itemsArray = array_filter($inputs['items']);
        $order = new Order();
        $order->country_id = $request['country'];
        $order->mail_to = $request['send_as_email'];
        $order->format = $request['format'];
        $order->email = $request['email'];
        $order->total = 0.0;
        $order->save();

        $order->total = $order->calculateTotal($itemsArray, $request['country'], $order->id);
        $order->save();
        $country = Country::find($order->country_id);

        if ($request->send_as_email == 1) {
            if ($request['format'] === 'PDF') {
                $html = view('order/show')
                    ->with('order', $order)
                    ->with('country', $country);
                $pdf = PDF::loadHTML($html)->setPaper('a4')->setWarnings(false)->stream();
                Mail::send('order/show', ['order' => $order, 'country' => $country],
                    function ($message) use ($order, $pdf) {
                        $message->from('parpiordercreator@gmail.com', 'OrderCreator');
                        $message->to($order->email)
                            ->subject('Order #' . $order->id);
                        $message->attachData($pdf, 'invoice.pdf');
                    });
            }
            else {
                Mail::send('order/show', ['order' => $order, 'country' => $country],
                    function ($message) use ($order) {
                        $message->from('parpiordercreator@gmail.com', 'OrderCreator');
                        $message->to($order->email)
                            ->subject('Order #' . $order->id);
                    });
            }
        }

        if ($request['format'] === "JSON") {
            $invoice = [];
            $invoice['id'] = $order->id;
            $invoice['country'] = $country->name;
            $invoice['mailed'] = $order->mail_to == 1 ? 'Yes' : 'No';
            $invoice['email'] = $order->email;
            $invoice['order_total'] = $order->total;
            $invoice['order_items'] = [];
            foreach($order->items as $item){
                $invoice['order_items'][] = array(
                    'name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->pivot->quantity,
                    'vat_amount' => $item->pivot->vat
                );
            }
            return json_encode($invoice);
        }

        return redirect('order/' . $order->id);
    }
}

@extends('index')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="row col-md-12">
                <h3>Order ID: <?= $order->id ?></h3>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div>
                        Country: <?= $country->name ?>
                    </div>
                    <div>
                        Mail sent: <?= $order->mail_to == 1 ? 'Yes' : 'No' ?>
                    </div>
                    <div>
                        Format: <?= $order->format ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Price + VAT</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td><?= $item->name ?></td>
                                <td><?= $item->pivot->quantity ?></td>
                                <td><?= $item->price ?> + <?= $item->pivot->vat ?></td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td>Total</td>
                            <td><?= $order->total ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')

@endsection
@extends('index')

@section('content')
    <div class="page-header row">
        <div class="col-md-12">
            <h3 style="display: inline-block">Countries and VAT</h3>
        </div>
    </div>

    <div class="col-md-6">
        @if(count($countries) > 0)
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>VATs</th>
                </tr>
                </thead>
                <tbody>
                @foreach($countries as $country)
                    <tr>
                        <td><?= $country->id ?></td>
                        <td><?= $country->name ?></td>
                        <td>
                            @foreach($country->vats as $vat)
                                <?= $vat->name . ": " . $vat->pivot->amount ."%"?> <br>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

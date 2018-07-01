@extends('index')

@section('content')
    <div class="page-header row">
        <div class="col-md-12">
            <h3 style="display: inline-block">Available products</h3>
        </div>
    </div>

    <div class="col-md-12">
        @if(count($items) > 0)
            <table class="table table-striped table-hover groups">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price (no VAT)</th>
                    <th>Divisible (you can buy 0,7kg etc.)</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td><?= $item->id ?></td>
                        <td><?= $item->name ?></td>
                        <td><?= $item->price ?></td>
                        <td><?= ($item->divisible == 1) ? 'Yes' : 'No'?></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

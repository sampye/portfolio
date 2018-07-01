@extends('index')

@section('content')
    <div class="page-header row user-header">
        <div class="col-md-12">
            <h3>Create Order</h3>
        </div>
    </div>

    <div role="tabpanel" class="tab-pane active" id="user">
        <?= Form::open(array(
            'url' => 'order/create',
            'role' => 'form',
            'class' => 'form'
        )) ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <?= Form::label('country', 'Country (VAT)') ?>
                    <select class="form-control" name="country">
                        @foreach($countries as $country)
                            <option name="country" value="<?=$country->id?>"><?= $country->name?></option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <?= Form::label('format', 'Format of Return') ?>
                    <select class="form-control" name="format">
                        <option value="HTML">HTML</option>
                        <option value="JSON">JSON</option>
                        <option value="PDF">PDF (by email)</option>
                    </select>
                </div>

                <div class="form-group">
                    <?= Form::label('send_as_email', 'Send as email?') ?><br>
                        <select class="form-control" name="send_as_email">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>

                </div>

                <div class="form-group">
                    <?= Form::label('email', 'Email') ?>
                    <?= Form::email('email', NULL, array(
                        'class' => 'form-control'
                    )) ?>
                </div>
                <?= Form::submit('Save the order', [
                    'class' => 'btn btn-primary pull-right'
                ]) ?>
            </div>
            <div class="col-md-6">
                <table class="table table-striped table-hover">
                    <tbody>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Amount to order</th>
                    </tr>
                    @foreach($items as $item)
                        <tr>
                            <td><?= $item->name ?></td>
                            <td><?= $item->price ?></td>
                            <td><?= Form::text('items[' . $item->id . ']') ?>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <?= Form::close() ?>
    </div>

@endsection

@section('scripts')
@endsection
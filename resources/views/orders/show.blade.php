@extends('layouts.app')

@section('content')
    @include('orders.partials.view-order-inner', compact('order', 'tables', 'mixes', 'tobaccos', 'masters'))
@endsection
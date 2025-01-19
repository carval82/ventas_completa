@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Regularización de Productos</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Este proceso actualizará:
                <ul>
                    <li>Stock total de productos</li>
                    <li>Precios de compra según últimos movimientos</li>
                    <li>Estados de productos</li>
                </ul>
            </div>

            <form action="{{ route('regularizacion.ejecutar') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="return confirm('¿Está seguro de ejecutar la regularización?')">
                    <i class="fas fa-sync"></i> Ejecutar Regularización
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Imprimir Factura Electrónica')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Factura Electrónica #{{ $venta->numero_factura_alegra ?? $venta->numero }}</h5>
                    <div>
                        @if (!empty($detalles['pdf_url']))
                            <a href="{{ $detalles['pdf_url'] }}" target="_blank" class="btn btn-sm btn-primary mr-1">
                                <i class="fas fa-file-pdf"></i> Ver PDF Original Alegra
                            </a>
                        @endif

                        <a href="{{ route('facturas.electronicas.descargar-pdf', $venta->id) }}" target="_blank" class="btn btn-sm btn-success mr-1">
                            <i class="fas fa-file-alt"></i> Carta (PDF propio)
                        </a>

                        <a href="{{ route('facturas.electronicas.imprimir-tirilla', $venta->id) }}" target="_blank" class="btn btn-sm btn-info mr-1">
                            <i class="fas fa-receipt"></i> Tirilla (PDF)
                        </a>

                        <button type="button" onclick="window.print();" class="btn btn-sm btn-outline-secondary mr-1">
                            <i class="fas fa-print"></i> Imprimir HTML
                        </button>

                        <a href="{{ route('facturas.electronicas.show', $venta->id) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body" id="printable-area">
                    <!-- Encabezado de la factura -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            @if($venta->empresa && $venta->empresa->logo)
                                <img src="{{ asset('storage/' . $venta->empresa->logo) }}" alt="Logo" style="max-width: 200px; height: auto; margin-bottom: 15px;">
                            @endif
                            <h4>{{ $venta->empresa->nombre_comercial ?? config('app.name') }}</h4>
                            @if($venta->empresa && $venta->empresa->razon_social && $venta->empresa->razon_social !== $venta->empresa->nombre_comercial)
                                <p><strong>{{ $venta->empresa->razon_social }}</strong></p>
                            @endif
                            @if($venta->empresa && $venta->empresa->nit)
                                <p><strong>NIT:</strong> {{ $venta->empresa->nit }}</p>
                            @endif
                            <p>
                                <strong>Dirección:</strong> {{ $venta->empresa->direccion ?? 'N/A' }}<br>
                                <strong>Teléfono:</strong> {{ $venta->empresa->telefono ?? 'N/A' }}<br>
                                <strong>Email:</strong> {{ $venta->empresa->email ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <h4>Factura Electrónica</h4>
                            <p>
                                <strong>Número:</strong> {{ $venta->numero_factura_alegra ?? $venta->numero }}<br>
                                <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($venta->fecha_venta ?? $venta->fecha)->format('d/m/Y') }}<br>
                                <strong>Estado DIAN:</strong> {{ $venta->estado_dian ?? 'Pendiente' }}
                            </p>
                        </div>
                    </div>

                    <!-- Información del cliente -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Cliente</h5>
                            <p>
                                <strong>Nombre:</strong> {{ $venta->cliente->nombres }} {{ $venta->cliente->apellidos }}<br>
                                <strong>Identificación:</strong> {{ $venta->cliente->cedula }}<br>
                                <strong>Dirección:</strong> {{ $venta->cliente->direccion ?? 'N/A' }}<br>
                                <strong>Teléfono:</strong> {{ $venta->cliente->telefono ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <h5>Detalles de Pago</h5>
                            <p>
                                <strong>Método de Pago:</strong> {{ $venta->metodo_pago ?? 'Efectivo' }}<br>
                                <strong>Estado:</strong> {{ $venta->estado_pago ?? 'Pagado' }}<br>
                                @if($venta->fecha_vencimiento)
                                <strong>Fecha Vencimiento:</strong> {{ \Carbon\Carbon::parse($venta->fecha_vencimiento)->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Resolución DIAN -->
                    @if(isset($detalles['factura']['numberTemplate']['resolution']))
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>Resolución DIAN:</strong> 
                                Número {{ $detalles['factura']['numberTemplate']['resolution']['number'] ?? 'N/A' }} 
                                del {{ isset($detalles['factura']['numberTemplate']['resolution']['date']) ? \Carbon\Carbon::parse($detalles['factura']['numberTemplate']['resolution']['date'])->format('d/m/Y') : 'N/A' }}
                                al {{ isset($detalles['factura']['numberTemplate']['resolution']['expirationDate']) ? \Carbon\Carbon::parse($detalles['factura']['numberTemplate']['resolution']['expirationDate'])->format('d/m/Y') : 'N/A' }}
                                <br>
                                <strong>Prefijo:</strong> {{ $detalles['factura']['numberTemplate']['prefix'] ?? 'N/A' }}
                                <strong>Rango:</strong> {{ $detalles['factura']['numberTemplate']['initialNumber'] ?? 'N/A' }} - {{ $detalles['factura']['numberTemplate']['finalNumber'] ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Tabla de productos -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-right">Precio Unitario</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($venta->detalles as $detalle)
                                <tr>
                                    <td>{{ $detalle->producto->nombre }}</td>
                                    <td class="text-center">{{ $detalle->cantidad }}</td>
                                    <td class="text-right">{{ number_format($detalle->precio, 2) }}</td>
                                    <td class="text-right">{{ number_format($detalle->cantidad * $detalle->precio, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Subtotal</strong></td>
                                    <td class="text-right">{{ number_format($venta->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>IVA ({{ $venta->iva_porcentaje ?? '19' }}%)</strong></td>
                                    <td class="text-right">{{ number_format($venta->iva, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Total</strong></td>
                                    <td class="text-right"><strong>{{ number_format($venta->total, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- CUFE y Datos Adicionales -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Información DIAN</h5>
                            <p>
                                <strong>CUFE:</strong>
                                @if($venta->cufe)
                                    {{ $venta->cufe }}
                                @elseif($venta->estado_dian === 'sent' || $venta->estado_dian === 'issued' || $venta->estado_dian === 'accepted')
                                    Factura enviada a DIAN. El CUFE aún está en proceso de generación. Puede tardar unos minutos en estar disponible.
                                @else
                                    Aún no se ha enviado la factura a la DIAN o está en proceso inicial.
                                @endif
                            </p>
                            @if($venta->qr_code || (isset($detalles['stamp']['barCodeContent']) && $detalles['stamp']['barCodeContent']))
                                <div class="mt-2">
                                    <strong>Código QR:</strong>
                                    <div class="mt-2">
                                        @if($venta->qr_code)
                                            <img src="data:image/png;base64,{{ $venta->qr_code }}" alt="QR DIAN" style="max-width: 150px;">
                                        @elseif(isset($detalles['stamp']['barCodeContent']))
                                            @php
                                                $qrData = $detalles['stamp']['barCodeContent'];
                                                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrData);
                                            @endphp
                                            <img src="{{ $qrUrl }}" alt="QR DIAN" style="max-width: 150px;">
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Notas y Observaciones -->
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>Observaciones:</strong> {{ $venta->observaciones ?? 'Gracias por su compra' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css" media="print">
    @page {
        size: auto;
        margin: 10mm;
    }
    body {
        margin: 0;
        padding: 0;
    }
    .card-header, .btn, .no-print {
        display: none !important;
    }
    .container-fluid {
        width: 100%;
        padding: 0;
    }
    .card {
        border: none !important;
    }
    .card-body {
        padding: 0 !important;
    }
</style>
@endsection

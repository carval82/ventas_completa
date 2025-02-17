<div class="card-body">
    <form action="{{ route('empresa.update', $empresa) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <!-- ... otros campos ... -->

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Régimen Tributario</label>
                <select name="regimen_tributario" class="form-select" required>
                    <option value="no_responsable_iva" {{ $empresa->regimen_tributario === 'no_responsable_iva' ? 'selected' : '' }}>
                        No Responsable de IVA
                    </option>
                    <option value="responsable_iva" {{ $empresa->regimen_tributario === 'responsable_iva' ? 'selected' : '' }}>
                        Responsable de IVA
                    </option>
                    <option value="regimen_simple" {{ $empresa->regimen_tributario === 'regimen_simple' ? 'selected' : '' }}>
                        Régimen Simple
                    </option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Resolución de Facturación</label>
                <input type="text" name="resolucion_facturacion" class="form-control" 
                       value="{{ $empresa->resolucion_facturacion }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha de Resolución</label>
                <input type="date" name="fecha_resolucion" class="form-control" 
                       value="{{ $empresa->fecha_resolucion?->format('Y-m-d') }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="factura_electronica_habilitada" 
                           class="form-check-input" value="1" 
                           {{ $empresa->factura_electronica_habilitada ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Facturación Electrónica Habilitada
                    </label>
                </div>
            </div>
        </div>

        <!-- ... botones de guardar ... -->
    </form>
</div> 
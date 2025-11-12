<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Empresa;

class UpdateEmpresaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Obtener la empresa actual
        $empresa = Empresa::first();
        $empresaId = $empresa ? $empresa->id : null;
        
        // Reglas base para todos los campos
        $rules = [
            'nombre_comercial' => 'sometimes|required|string|max:255',
            'razon_social' => 'sometimes|required|string|max:255',
            'nit' => 'sometimes|required|string|unique:empresas,nit,' . $empresaId,
            'direccion' => 'sometimes|required|string|max:255',
            'telefono' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|nullable|email|max:255',
            'sitio_web' => 'sometimes|nullable|url|max:255',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,svg|max:1024',
            'formato_impresion' => 'sometimes|required|in:58mm,80mm,media_carta',
            'generar_qr_local' => 'sometimes|nullable|boolean',
            'regimen_tributario' => 'sometimes|required|in:responsable_iva,no_responsable_iva,regimen_simple',
            'resolucion_facturacion' => 'sometimes|nullable|string',
            'fecha_resolucion' => 'sometimes|nullable|date',
            'fecha_vencimiento_resolucion' => 'sometimes|nullable|date|after_or_equal:fecha_resolucion',
            'factura_electronica_habilitada' => 'sometimes|nullable|boolean',
            'alegra_email' => 'sometimes|nullable|email|max:255',
            'alegra_token' => 'sometimes|nullable|string|max:255',
            'prefijo_factura' => 'sometimes|nullable|string|max:50',
            'id_resolucion_alegra' => 'sometimes|nullable|string|max:255',
            'numero_resolucion' => 'sometimes|nullable|string|max:255',
        ];
        
        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre_comercial' => 'nombre comercial',
            'razon_social' => 'razón social',
            'nit' => 'NIT',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
            'sitio_web' => 'sitio web',
            'logo' => 'logo',
            'regimen_tributario' => 'régimen tributario',
            'resolucion_facturacion' => 'resolución de facturación',
            'fecha_resolucion' => 'fecha de resolución',
            'fecha_vencimiento_resolucion' => 'fecha de vencimiento de resolución',
            'factura_electronica_habilitada' => 'facturación electrónica habilitada',
            'alegra_email' => 'correo electrónico de Alegra',
            'alegra_token' => 'token de API de Alegra',
            'prefijo_factura' => 'prefijo de factura',
            'id_resolucion_alegra' => 'ID de resolución en Alegra',
        ];
    }
}

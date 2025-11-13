<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\AlegraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UpdateEmpresaRequest;

class EmpresaController extends Controller
{
    protected $alegraService;

    public function __construct(AlegraService $alegraService = null)
    {
        $this->middleware('auth');
        
        $this->alegraService = $alegraService ?? new AlegraService();
    }

    public function index()
    {
        $empresa = Empresa::first();
        return view('configuracion.empresa.index', compact('empresa'));
    }

    public function create()
    {
        return view('configuracion.empresa.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_comercial' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'nit' => 'required|string|unique:empresas,nit',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:1024',
            'regimen_tributario' => 'required|in:responsable_iva,no_responsable_iva,regimen_simple',
            'resolucion_facturacion' => 'nullable|string|max:255',
            'fecha_resolucion' => 'nullable|date',
            'factura_electronica_habilitada' => 'nullable|boolean'
        ]);

        try {
            $data = $request->except('logo');
            
            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('logos', 'public');
            }

            // Asegurarse de que los checkboxes se manejen correctamente
            $data['factura_electronica_habilitada'] = $request->has('factura_electronica_habilitada');
            $data['alegra_multiples_impuestos'] = $request->has('alegra_multiples_impuestos');
            
            // Asegurar campos requeridos con valores por defecto
            if (empty($data['resolucion_facturacion'])) {
                $data['resolucion_facturacion'] = 'No aplica';
            }

            Empresa::create($data);

            return redirect()->route('empresa.index')
                           ->with('success', 'Información de la empresa registrada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar la información de la empresa')
                        ->withInput();
        }
    }

    public function show(Empresa $empresa)
    {
        return view('configuracion.empresa.show', compact('empresa'));
    }

    public function edit()
    {
        $empresa = Empresa::first();
        
        if (!$empresa) {
            // Si no hay empresa, crear una nueva con valores por defecto
            $empresa = new Empresa([
                'nombre_comercial' => '',
                'razon_social' => '',
                'nit' => '',
                'direccion' => '',
                'telefono' => '',
                'email' => '',
                'regimen_tributario' => 'no_responsable_iva',
                'resolucion_facturacion' => 'No aplica',
                'factura_electronica_habilitada' => false,
                'alegra_multiples_impuestos' => false,
                'alegra_email' => config('alegra.user'),
                'alegra_token' => config('alegra.token')
            ]);
            $empresa->save();
        }
        
        return view('configuracion.empresa.edit', compact('empresa'));
    }

    public function update(UpdateEmpresaRequest $request)
    {
        $empresa = Empresa::first();
        
        if (!$empresa) {
            return redirect()->route('empresa.index')
                ->with('error', 'No se encontró la empresa para actualizar');
        }
        
        // Obtener solo los campos que se han enviado en el formulario
        $data = $request->except(['logo']); // Excluir logo porque se maneja por separado
        
        // Solo mantener los campos permitidos
        $camposPermitidos = [
            'nombre_comercial',
            'razon_social',
            'nit',
            'direccion',
            'telefono',
            'email',
            'sitio_web',
            'formato_impresion',
            'generar_qr_local',
            'regimen_tributario',
            'factura_electronica_habilitada',
            'alegra_email',
            'alegra_token',
            'prefijo_factura',
            'id_resolucion_alegra',
            'numero_resolucion'
        ];
        
        $data = array_intersect_key($data, array_flip($camposPermitidos));
        
        // Registrar los datos recibidos para depuración
        Log::info('Datos recibidos en update', [
            'request_all' => $request->all(),
            'data_filtered' => $data,
            'empresa_id' => $empresa->id
        ]);
        
        // Manejar el checkbox de facturación electrónica
        $data['factura_electronica_habilitada'] = $request->has('factura_electronica_habilitada');
        
        // Manejar el logo solo si se ha enviado
        if ($request->hasFile('logo')) {
            // Eliminar el logo anterior si existe
            if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                Storage::disk('public')->delete($empresa->logo);
            }
            
            // Guardar el nuevo logo
            $logoPath = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $logoPath;
            
            Log::info('Logo actualizado', [
                'ruta' => $logoPath
            ]);
        }
        
        // Si se proporcionaron credenciales de Alegra y se ha cambiado alguna, intentar obtener la resolución automáticamente
        if (!empty($data['alegra_email']) && !empty($data['alegra_token']) && 
            ($data['alegra_email'] != $empresa->alegra_email || $data['alegra_token'] != $empresa->alegra_token)) {
            
            // Crear instancia de AlegraService con las credenciales proporcionadas
            $alegra = new AlegraService($data['alegra_email'], $data['alegra_token']);
            
            // Probar conexión
            $resultado = $alegra->probarConexion();
            
            if ($resultado['success']) {
                // Si la conexión es exitosa, obtener la resolución de facturación electrónica
                $resolucion = $alegra->obtenerResolucionPreferida('electronica');
                
                if ($resolucion['success']) {
                    $resolucionFormateada = $this->formatearResolucion($resolucion['data']);
                    
                    // Actualizar datos de resolución
                    $data['resolucion_facturacion'] = json_encode($resolucionFormateada);
                    $data['prefijo_factura'] = $resolucionFormateada['prefijo'];
                    $data['id_resolucion_alegra'] = $resolucionFormateada['id'];
                    
                    // Convertir fechas de formato dd/mm/yyyy a Y-m-d
                    if (isset($resolucionFormateada['fecha_inicio']) && $resolucionFormateada['fecha_inicio'] !== 'No disponible') {
                        $fechaInicio = \DateTime::createFromFormat('d/m/Y', $resolucionFormateada['fecha_inicio']);
                        if ($fechaInicio) {
                            $data['fecha_resolucion'] = $fechaInicio->format('Y-m-d');
                        }
                    }
                    
                    if (isset($resolucionFormateada['fecha_fin']) && $resolucionFormateada['fecha_fin'] !== 'No disponible') {
                        $fechaFin = \DateTime::createFromFormat('d/m/Y', $resolucionFormateada['fecha_fin']);
                        if ($fechaFin) {
                            $data['fecha_vencimiento_resolucion'] = $fechaFin->format('Y-m-d');
                        }
                    }
                    
                    // Registrar en el log
                    Log::info('Resolución actualizada automáticamente', [
                        'resolucion' => $resolucionFormateada
                    ]);
                }
            }
        } else if (!empty($data['prefijo_factura']) && !empty($data['id_resolucion_alegra']) && 
                  ($data['prefijo_factura'] != $empresa->prefijo_factura || $data['id_resolucion_alegra'] != $empresa->id_resolucion_alegra)) {
            // Si no se proporcionaron credenciales de Alegra pero sí se proporcionaron manualmente
            // los datos de resolución y han cambiado, actualizar el campo resolucion_facturacion
            
            // Crear un objeto de resolución con los datos proporcionados
            $resolucionManual = [
                'texto' => 'Resolución DIAN No. ' . ($request->input('numero_resolucion') ?: 'No disponible') . 
                           ' del ' . ($request->input('fecha_resolucion') ? date('d/m/Y', strtotime($request->input('fecha_resolucion'))) : 'No disponible') .
                           ' - Numeración autorizada: ' . $data['prefijo_factura'] . '501 al ' . $data['prefijo_factura'] . '2000' .
                           ' - Vigencia hasta: ' . ($request->input('fecha_vencimiento_resolucion') ? date('d/m/Y', strtotime($request->input('fecha_vencimiento_resolucion'))) : 'No disponible'),
                'prefijo' => $data['prefijo_factura'],
                'id' => $data['id_resolucion_alegra'],
                'numero_resolucion' => $request->input('numero_resolucion') ?: 'No disponible',
                'fecha_inicio' => $request->input('fecha_resolucion') ? date('d/m/Y', strtotime($request->input('fecha_resolucion'))) : 'No disponible',
                'fecha_fin' => $request->input('fecha_vencimiento_resolucion') ? date('d/m/Y', strtotime($request->input('fecha_vencimiento_resolucion'))) : 'No disponible'
            ];
            
            // Actualizar el campo resolucion_facturacion como texto plano
            $data['resolucion_facturacion'] = $resolucionManual['texto'];
            
            // Registrar en el log
            Log::info('Resolución actualizada manualmente', [
                'resolucion' => $resolucionManual,
                'prefijo_factura' => $data['prefijo_factura'],
                'id_resolucion_alegra' => $data['id_resolucion_alegra']
            ]);
        }
        
        // Registrar los datos antes de actualizar
        Log::info('Datos antes de actualizar', [
            'data' => $data
        ]);
        
        // Actualizar la empresa solo con los campos que se han enviado
        $empresa->update($data);
        
        // Registrar los datos después de actualizar
        Log::info('Empresa actualizada', [
            'empresa' => $empresa->toArray()
        ]);
        
        return redirect()->route('empresa.index')
            ->with('success', 'Información de la empresa actualizada correctamente');
    }

    public function destroy(Empresa $empresa)
    {
        try {
            if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                Storage::disk('public')->delete($empresa->logo);
            }
            
            $empresa->delete();
            return redirect()->route('empresa.index')
                           ->with('success', 'Información de la empresa eliminada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar la información de la empresa');
        }
    }

    public function obtenerResolucionAlegra(Request $request)
    {
        try {
            Log::info('Iniciando solicitud de resolución Alegra');
            
            $resolucion = $this->alegraService->obtenerResolucionFacturacion();
            
            Log::info('Respuesta del servicio Alegra recibida', [
                'respuesta' => $resolucion
            ]);
            
            if ($resolucion['success']) {
                Log::info('Resolución obtenida correctamente', [
                    'data' => $resolucion['data']
                ]);
                
                return response()->json([
                    'success' => true,
                    'resolucion' => $resolucion['data']['number'],
                    'fecha' => $resolucion['data']['date']
                ]);
            }

            Log::warning('No se pudo obtener la resolución', [
                'error' => $resolucion['error']
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la resolución de Alegra: ' . 
                            ($resolucion['error'] ?? 'Error desconocido')
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error en el controlador al obtener resolución', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resolución: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verificarFacturacionElectronica()
    {
        try {
            $alegraService = app(AlegraService::class);
            $response = $alegraService->verificarFacturacionElectronica();
            
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error al verificar FE', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prueba la conexión con Alegra
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function probarConexion(Request $request)
    {
        try {
            $request->validate([
                'alegra_email' => 'required|email',
                'alegra_token' => 'required|string'
            ]);
            
            // Registrar los datos recibidos (sin mostrar el token completo)
            \Log::info('Probando conexión con Alegra desde la interfaz', [
                'email' => $request->alegra_email,
                'token_parcial' => substr($request->alegra_token, 0, 3) . '...' . substr($request->alegra_token, -3),
                'solo_verificar' => $request->solo_verificar
            ]);
            
            // Crear instancia de AlegraService con las credenciales proporcionadas
            $alegra = new AlegraService($request->alegra_email, $request->alegra_token);
            
            // Probar conexión
            $resultado = $alegra->probarConexion();
            
            \Log::info('Resultado de prueba de conexión', [
                'success' => $resultado['success'],
                'mensaje' => $resultado['message'] ?? ($resultado['error'] ?? 'No hay mensaje')
            ]);
            
            if ($resultado['success']) {
                // Si la conexión es exitosa, obtener la resolución de facturación electrónica
                $resolucion = $alegra->obtenerResolucionPreferida('electronica');
                
                \Log::info('Resultado de obtener resolución', [
                    'success' => $resolucion['success'],
                    'mensaje' => $resolucion['message'] ?? 'No hay mensaje'
                ]);
                
                if ($resolucion['success']) {
                    $resolucionFormateada = $this->formatearResolucion($resolucion['data']);
                    
                    // Obtener información de productos y clientes
                    $productos = $alegra->obtenerProductos();
                    $clientes = $alegra->obtenerClientes();
                    
                    // Obtener el cliente genérico (consumidor final)
                    $clienteGenerico = $alegra->obtenerClienteGenerico();
                    
                    // Solo actualizar la base de datos si no es solo verificación
                    if (!$request->has('solo_verificar') || $request->solo_verificar !== 'true') {
                        // Actualizar la empresa con las credenciales y la resolución
                        $empresa = Empresa::first();
                        
                        if ($empresa) {
                            $empresa->update([
                                'alegra_email' => $request->alegra_email,
                                'alegra_token' => $request->alegra_token,
                                'resolucion_facturacion' => json_encode($resolucionFormateada),
                                'prefijo_factura' => $resolucionFormateada['prefijo'],
                                'id_resolucion_alegra' => $resolucionFormateada['id']
                            ]);
                            
                            // Guardar el cliente genérico en la tabla de clientes si se encontró
                            if ($clienteGenerico['success'] && isset($clienteGenerico['data']['id'])) {
                                // Buscar si ya existe un cliente con este ID de Alegra
                                $clienteExistente = \App\Models\Cliente::where('id_alegra', $clienteGenerico['data']['id'])->first();
                                
                                if (!$clienteExistente) {
                                    // Si no existe, crear un nuevo cliente
                                    $cliente = new \App\Models\Cliente([
                                        'nombres' => 'Consumidor',
                                        'apellidos' => 'Final',
                                        'cedula' => '9999999999',
                                        'telefono' => '',
                                        'email' => '',
                                        'direccion' => '',
                                        'estado' => true,
                                        'id_alegra' => $clienteGenerico['data']['id']
                                    ]);
                                    $cliente->save();
                                    
                                    \Log::info('Cliente genérico guardado en la tabla de clientes', [
                                        'id' => $cliente->id,
                                        'id_alegra' => $cliente->id_alegra
                                    ]);
                                } else {
                                    \Log::info('Cliente genérico ya existe en la tabla de clientes', [
                                        'id' => $clienteExistente->id,
                                        'id_alegra' => $clienteExistente->id_alegra
                                    ]);
                                }
                            }
                            
                            // Convertir fechas de formato dd/mm/yyyy a Y-m-d
                            if (isset($resolucionFormateada['fecha_inicio']) && $resolucionFormateada['fecha_inicio'] !== 'No disponible') {
                                $fechaInicio = \DateTime::createFromFormat('d/m/Y', $resolucionFormateada['fecha_inicio']);
                                if ($fechaInicio) {
                                    $empresa->fecha_resolucion = $fechaInicio->format('Y-m-d');
                                }
                            }
                            
                            if (isset($resolucionFormateada['fecha_fin']) && $resolucionFormateada['fecha_fin'] !== 'No disponible') {
                                $fechaFin = \DateTime::createFromFormat('d/m/Y', $resolucionFormateada['fecha_fin']);
                                if ($fechaFin) {
                                    $empresa->fecha_vencimiento_resolucion = $fechaFin->format('Y-m-d');
                                }
                            }
                            
                            $empresa->save();
                        }
                    } else {
                        \Log::info('Solo verificación, no se actualizó la base de datos');
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Conexión exitosa con Alegra. Se ha obtenido la resolución de facturación electrónica.',
                        'resolucion' => $resolucionFormateada,
                        'productos' => $productos['success'] ? $productos['data'] : null,
                        'clientes' => $clientes['success'] ? $clientes['data'] : null,
                        'cliente_generico' => $clienteGenerico['success'] ? $clienteGenerico['data'] : null
                    ]);
                } else {
                    // Si no se pudo obtener la resolución, usar la configurada manualmente
                    $empresa = Empresa::first();
                    if ($empresa && !empty($empresa->resolucion_facturacion)) {
                        $resolucionManual = json_decode($empresa->resolucion_facturacion, true);
                        
                        if ($resolucionManual) {
                            \Log::info('Usando resolución manual configurada en la base de datos', [
                                'resolucion' => $resolucionManual
                            ]);
                            
                            return response()->json([
                                'success' => true,
                                'message' => 'Conexión exitosa con Alegra. Se está utilizando la resolución configurada manualmente.',
                                'resolucion' => $resolucionManual
                            ]);
                        }
                    } else {
                        // Si no hay resolución configurada, informar al usuario que debe configurarla
                        \Log::warning('No hay resolución configurada en la base de datos');
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Conexión exitosa con Alegra, pero no se pudo obtener ninguna resolución de facturación electrónica. Por favor, configure una resolución manualmente.',
                            'resolucion' => null
                        ]);
                    }
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Conexión exitosa con Alegra, pero no se pudo obtener la resolución de facturación electrónica: ' . $resolucion['message']
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo conectar con Alegra: ' . ($resultado['message'] ?? $resultado['error'] ?? 'Error desconocido')
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Excepción al probar conexión con Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al probar conexión con Alegra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cuenta los tipos de productos en un array de productos
     * 
     * @param array $productos
     * @return array
     */
    private function contarTiposProductos($productos)
    {
        $tipos = [];
        foreach ($productos as $producto) {
            $tipo = $producto['type'] ?? 'desconocido';
            if (!isset($tipos[$tipo])) {
                $tipos[$tipo] = 0;
            }
            $tipos[$tipo]++;
        }
        return $tipos;
    }

    /**
     * Sincroniza los clientes y productos con Alegra
     * 
     * @param Empresa $empresa
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sincronizarAlegra(Empresa $empresa)
    {
        try {
            // Verificar que la empresa tenga credenciales de Alegra
            if (empty($empresa->alegra_email) || empty($empresa->alegra_token)) {
                session()->flash('error', 'La empresa no tiene configuradas las credenciales de Alegra');
                return redirect()->route('empresa.edit', $empresa);
            }
            
            // Crear una instancia del servicio con las credenciales de la empresa
            $alegraService = new \App\Services\AlegraService(
                $empresa->alegra_email,
                $empresa->alegra_token
            );
            
            // Sincronizar clientes
            $resultadoClientes = $alegraService->sincronizarTodosLosClientes();
            
            // Sincronizar productos
            $resultadoProductos = $alegraService->sincronizarTodosLosProductos();
            
            // Preparar mensaje de respuesta
            $mensajeClientes = '';
            $mensajeProductos = '';
            
            if ($resultadoClientes['success']) {
                $mensajeClientes = 'Se sincronizaron ' . $resultadoClientes['data']['sincronizados'] . ' de ' . 
                    $resultadoClientes['data']['total'] . ' clientes con Alegra.';
                
                if ($resultadoClientes['data']['errores'] > 0) {
                    $mensajeClientes .= ' Hubo ' . $resultadoClientes['data']['errores'] . ' errores.';
                }
            } else {
                $mensajeClientes = 'Error al sincronizar clientes: ' . ($resultadoClientes['error'] ?? 'Error desconocido');
            }
            
            if ($resultadoProductos['success']) {
                $mensajeProductos = 'Se sincronizaron ' . $resultadoProductos['data']['sincronizados'] . ' de ' . 
                    $resultadoProductos['data']['total'] . ' productos con Alegra.';
                
                if ($resultadoProductos['data']['errores'] > 0) {
                    $mensajeProductos .= ' Hubo ' . $resultadoProductos['data']['errores'] . ' errores.';
                }
            } else {
                $mensajeProductos = 'Error al sincronizar productos: ' . ($resultadoProductos['error'] ?? 'Error desconocido');
            }
            
            // Mostrar mensaje de éxito o error
            if ($resultadoClientes['success'] || $resultadoProductos['success']) {
                session()->flash('success', 'Sincronización con Alegra completada. ' . $mensajeClientes . ' ' . $mensajeProductos);
            } else {
                session()->flash('error', 'Error al sincronizar con Alegra. ' . $mensajeClientes . ' ' . $mensajeProductos);
            }
            
            return redirect()->route('empresa.edit', $empresa);
            
        } catch (\Exception $e) {
            Log::error('Error al sincronizar con Alegra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Error al sincronizar con Alegra: ' . $e->getMessage());
            return redirect()->route('empresa.edit', $empresa);
        }
    }

    /**
     * Formatea la información de resolución de Alegra en un formato legible
     * 
     * @param array $resolucionData Datos de la resolución de Alegra
     * @return array
     */
    private function formatearResolucion($resolucion)
    {
        if (empty($resolucion) || !isset($resolucion['id'])) {
            return [
                'texto' => 'No se encontró una resolución de facturación electrónica activa',
                'prefijo' => '',
                'id' => '',
                'numero_resolucion' => ''
            ];
        }

        // Formatear las fechas en formato dd/mm/yyyy
        $fechaInicio = isset($resolucion['resolutionDate']) 
            ? date('d/m/Y', strtotime($resolucion['resolutionDate'])) 
            : (isset($resolucion['startDate']) 
                ? date('d/m/Y', strtotime($resolucion['startDate'])) 
                : 'No disponible');
                
        $fechaFin = isset($resolucion['resolutionEndDate']) 
            ? date('d/m/Y', strtotime($resolucion['resolutionEndDate'])) 
            : (isset($resolucion['endDate']) 
                ? date('d/m/Y', strtotime($resolucion['endDate'])) 
                : 'No disponible');
        
        // Formatear el número de resolución
        $numeroResolucion = $resolucion['resolution'] ?? $resolucion['resolutionNumber'] ?? 'No disponible';
        $numeroResolucion = is_array($numeroResolucion) ? json_encode($numeroResolucion) : $numeroResolucion;
        
        // Formatear el prefijo
        $prefijo = $resolucion['prefix'] ?? '';
        $prefijo = is_array($prefijo) ? '' : $prefijo;
        
        // Formatear el rango de numeración
        $minNumber = $resolucion['initialNumber'] ?? $resolucion['minInvoiceNumber'] ?? 'No disponible';
        $minNumber = is_array($minNumber) ? 'No disponible' : $minNumber;
        $maxNumber = $resolucion['finalNumber'] ?? $resolucion['maxInvoiceNumber'] ?? 'No disponible';
        $maxNumber = is_array($maxNumber) ? 'No disponible' : $maxNumber;
        
        // Crear texto completo de la resolución
        $texto = "Resolución DIAN No. {$numeroResolucion} del {$fechaInicio} - ";
        $texto .= "Numeración autorizada: {$prefijo}{$minNumber} al {$prefijo}{$maxNumber} - ";
        $texto .= "Vigencia hasta: {$fechaFin}";
        
        return [
            'texto' => $texto,
            'prefijo' => $prefijo,
            'id' => (string)$resolucion['id'], // Asegurar que el ID sea un string
            'numero_resolucion' => $numeroResolucion,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ];
    }
}
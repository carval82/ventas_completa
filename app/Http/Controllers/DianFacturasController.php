<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionDian;
use App\Models\FacturaDianProcesada;
use App\Models\EmailBuzon;
use App\Models\Empresa;
use App\Services\Dian\EmailProcessorService;
use App\Services\Dian\AcuseGeneratorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DianFacturasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Verificar que el usuario tenga empresa asociada
     */
    private function verificarEmpresa()
    {
        $empresa = auth()->user()->empresa;
        
        if (!$empresa) {
            return redirect()->route('home')->with('error', 'Debe tener una empresa asociada para usar el módulo DIAN');
        }
        
        return $empresa;
    }

    /**
     * Dashboard principal del módulo DIAN
     */
    public function index()
    {
        Log::info('DIAN Dashboard: Acceso al dashboard', [
            'usuario_id' => auth()->id(),
            'usuario_email' => auth()->user()->email
        ]);

        $empresa = auth()->user()->empresa;
        
        // Verificar que el usuario tenga una empresa asociada
        if (!$empresa) {
            Log::warning('DIAN Dashboard: Usuario sin empresa asociada', [
                'usuario_id' => auth()->id(),
                'usuario_email' => auth()->user()->email
            ]);
            return redirect()->route('home')->with('error', 'Debe tener una empresa asociada para usar el módulo DIAN');
        }
        
        Log::info('DIAN Dashboard: Empresa verificada', [
            'empresa_id' => $empresa->id,
            'empresa_nombre' => $empresa->nombre
        ]);
        
        $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
        
        // Estadísticas generales
        $estadisticas = $this->obtenerEstadisticas($empresa->id);
        Log::info('DIAN Dashboard: Estadísticas calculadas', [
            'empresa_id' => $empresa->id,
            'estadisticas' => $estadisticas
        ]);
        
        // Obtener emails con facturas del buzón (sistema nuevo)
        $facturas = EmailBuzon::where('empresa_id', $empresa->id)
            ->where('tiene_facturas', true)
            ->orderBy('fecha_email', 'desc')
            ->limit(10)
            ->get();

        // Obtener estadísticas del buzón de correos
        $estadisticasBuzon = null;
        if ($configuracion) {
            try {
                $buzonService = new \App\Services\Dian\BuzonEmailService($configuracion);
                $estadisticasBuzon = $buzonService->obtenerEstadisticas();
            } catch (\Exception $e) {
                Log::warning('DIAN Dashboard: Error obteniendo estadísticas del buzón', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('DIAN Dashboard: Facturas obtenidas', [
            'empresa_id' => $empresa->id,
            'total_facturas' => $facturas->count()
        ]);

        return view('dian.dashboard', compact(
            'configuracion',
            'estadisticas',
            'facturas',
            'estadisticasBuzon'
        ));
    }

    /**
     * Mostrar configuración DIAN
     */
    public function configuracion()
    {
        Log::info('DIAN Configuración: Acceso a configuración', [
            'usuario_id' => auth()->id(),
            'usuario_email' => auth()->user()->email
        ]);

        $empresa = auth()->user()->empresa;
        
        if (!$empresa) {
            Log::warning('DIAN Configuración: Usuario sin empresa asociada', [
                'usuario_id' => auth()->id()
            ]);
            return redirect()->route('home')->with('error', 'Debe tener una empresa asociada para usar el módulo DIAN');
        }
        
        $configuracion = ConfiguracionDian::where('empresa_id', $empresa->id)->first();
        
        if (!$configuracion) {
            Log::info('DIAN Configuración: Creando nueva configuración', [
                'empresa_id' => $empresa->id
            ]);
            $configuracion = new ConfiguracionDian([
                'empresa_id' => $empresa->id
            ]);
        } else {
            Log::info('DIAN Configuración: Configuración existente encontrada', [
                'empresa_id' => $empresa->id,
                'configuracion_id' => $configuracion->id,
                'email_configurado' => $configuracion->email_dian,
                'activo' => $configuracion->activo
            ]);
        }

        // Obtener configuraciones predefinidas y detectar configuración existente
        $configuracionesPredefinidas = \App\Services\Dian\EmailProcessorService::obtenerConfiguracionesPredefinidas();
        $configExistente = \App\Services\Dian\EmailProcessorService::detectarConfiguracionExistente();

        Log::info('DIAN Configuración: Configuraciones preparadas', [
            'predefinidas_count' => count($configuracionesPredefinidas),
            'config_existente_encontrada' => $configExistente['configuracion_encontrada']
        ]);

        return view('dian.configuracion', compact('configuracion', 'configuracionesPredefinidas', 'configExistente'));
    }

    /**
     * Autocompletar configuración desde Gmail
     */
    public function autocompletarDesdeGmail()
    {
        try {
            $empresa = auth()->user()->empresa;
            
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe tener una empresa asociada'
                ]);
            }

            Log::info('DIAN Autocompletar: Iniciando autocompletado', [
                'empresa_id' => $empresa->id,
                'usuario_id' => auth()->id()
            ]);

            $configExistente = \App\Services\Dian\EmailProcessorService::detectarConfiguracionExistente();
            
            if (!$configExistente['configuracion_encontrada']) {
                Log::warning('DIAN Autocompletar: No se encontró configuración en variables de entorno');
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró configuración de email en las variables de entorno. Configura manualmente desde la interfaz.'
                ]);
            }

            // IMPORTANTE: Solo sugerir valores, NO sobrescribir configuración existente
            $configuracionActual = ConfiguracionDian::where('empresa_id', $empresa->id)->first();
            
            // Preparar configuración sugerida (sin guardar automáticamente)
            $configuracionSugerida = [
                'email_dian' => $configExistente['email_detectado'],
                'servidor_imap' => $configExistente['servidor_detectado'],
                'puerto_imap' => $configExistente['puerto_detectado'],
                'ssl_enabled' => $configExistente['ssl_detectado'],
                'email_remitente' => $configExistente['email_detectado'],
                'nombre_remitente' => $empresa->nombre ?? 'Mi Empresa'
            ];

            Log::info('DIAN Autocompletar: Configuración sugerida preparada', [
                'email_sugerido' => $configuracionSugerida['email_dian'],
                'servidor_sugerido' => $configuracionSugerida['servidor_imap']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuración sugerida desde variables de entorno. Revisa y guarda si es correcta.',
                'configuracion' => $configuracionSugerida,
                'nota' => 'Esta configuración es solo una sugerencia. Puedes modificarla antes de guardar.'
            ]);

        } catch (\Exception $e) {
            Log::error('DIAN Autocompletar: Error en autocompletado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error autocompletando configuración: ' . $e->getMessage()
            ]);
        }
    }
    /**
     * Guardar configuración DIAN
     */
    public function guardarConfiguracion(Request $request)
    {
        Log::info('DIAN Configuración: Iniciando guardado de configuración', [
            'usuario_id' => auth()->id(),
            'datos_recibidos' => $request->except(['password_email', '_token'])
        ]);

        $validator = Validator::make($request->all(), [
            'email_dian' => 'required|email',
            'password_email' => 'required|string',
            'servidor_imap' => 'required|string',
            'puerto_imap' => 'required|integer|min:1|max:65535',
            'email_remitente' => 'nullable|email',
            'nombre_remitente' => 'nullable|string|max:255',
            'plantilla_acuse' => 'nullable|string',
            'frecuencia_minutos' => 'required|integer|min:5|max:1440',
            'hora_inicio' => 'required|date_format:H:i:s',
            'hora_fin' => 'required|date_format:H:i:s|after:hora_inicio'
        ]);

        if ($validator->fails()) {
            Log::warning('DIAN Configuración: Validación fallida', [
                'errores' => $validator->errors()->toArray()
            ]);
            return back()->withErrors($validator)->withInput();
        }

        try {
            $empresa = auth()->user()->empresa;
            
            if (!$empresa) {
                Log::error('DIAN Configuración: Usuario sin empresa asociada');
                return back()->with('error', 'Debe tener una empresa asociada para usar el módulo DIAN');
            }
            
            $configuracion = ConfiguracionDian::updateOrCreate(
                ['empresa_id' => $empresa->id],
                $request->only([
                    'email_dian',
                    'password_email',
                    'servidor_imap',
                    'puerto_imap',
                    'ssl_enabled',
                    'email_remitente',
                    'nombre_remitente',
                    'plantilla_acuse',
                    'procesamiento_automatico',
                    'frecuencia_minutos',
                    'hora_inicio',
                    'hora_fin'
                ])
            );

            Log::info('DIAN Configuración: Configuración guardada exitosamente', [
                'empresa_id' => $empresa->id,
                'configuracion_id' => $configuracion->id,
                'email_dian' => $configuracion->email_dian,
                'servidor_imap' => $configuracion->servidor_imap,
                'puerto_imap' => $configuracion->puerto_imap,
                'ssl_enabled' => $configuracion->ssl_enabled
            ]);

            return redirect()->route('dian.configuracion')
                           ->with('success', 'Configuración guardada exitosamente');

        } catch (\Exception $e) {
            Log::error('DIAN Configuración: Error guardando configuración', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'usuario_id' => auth()->id(),
                'empresa_id' => $empresa->id ?? null
            ]);
            return back()->with('error', 'Error guardando configuración: ' . $e->getMessage());
        }
    }

    /**
     * Probar conexión email
     */
    public function probarConexion(Request $request)
    {
        Log::info('DIAN Conexión: Iniciando prueba de conexión IMAP', [
            'usuario_id' => auth()->id(),
            'datos_conexion' => $request->except(['password_email', '_token'])
        ]);

        try {
            // Verificar si IMAP está disponible
            if (!extension_loaded('imap')) {
                Log::warning('DIAN Conexión: Extensión IMAP no disponible, usando método alternativo');
                
                // Usar servicio alternativo
                $gmailService = new \App\Services\Dian\GmailApiService(
                    $request->input('email_dian'),
                    $request->input('password_email')
                );
                
                $resultado = $gmailService->probarConexion();
                
                Log::info('DIAN Conexión: Resultado método alternativo', $resultado);
                
                if ($resultado['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => '✅ Conexión exitosa usando método alternativo. ' . $resultado['message'],
                        'metodo' => 'Alternativo (Socket)',
                        'nota' => 'IMAP no disponible, pero la conexión al servidor Gmail funciona correctamente.',
                        'solucion_imap' => [
                            'paso1' => 'Para habilitar IMAP: Abrir C:\\xampp\\php\\php.ini',
                            'paso2' => 'Buscar ;extension=imap y cambiar a extension=imap',
                            'paso3' => 'Reiniciar Apache en XAMPP',
                            'paso4' => 'El módulo funcionará completamente con IMAP habilitado'
                        ]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => '❌ Error de conexión: ' . $resultado['message'],
                        'metodo' => 'Alternativo (Socket)',
                        'solucion' => [
                            'paso1' => 'Verificar credenciales de Gmail',
                            'paso2' => 'Verificar conexión a internet',
                            'paso3' => 'Habilitar IMAP en php.ini para funcionalidad completa'
                        ]
                    ]);
                }
            }

            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                Log::warning('DIAN Conexión: No hay configuración DIAN activa', [
                    'empresa_id' => $empresa->id ?? null
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración DIAN activa'
                ]);
            }

            // Crear instancia temporal con datos del formulario
            $configTemp = new ConfiguracionDian($request->all());
            $configTemp->empresa_id = $empresa->id;
            
            $emailProcessor = new EmailProcessorService($configTemp);
            
            // Probar conexión (método simplificado)
            $resultado = $this->probarConexionIMAP($configTemp);
            
            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error probando conexión: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar emails manualmente
     */
    public function procesarEmails()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN activa');
            }

            Log::info('DIAN Procesamiento: Iniciando procesamiento de emails', [
                'empresa_id' => $empresa->id,
                'configuracion_id' => $configuracion->id
            ]);

            // Usar Buzón de Correos propio (estilo Outlook)
            Log::info('DIAN Procesamiento: Usando Buzón de Correos propio');
            
            $buzonService = new \App\Services\Dian\BuzonEmailService($configuracion);
            
            // Paso 1: Sincronizar emails desde servidor
            $resultadosSincronizacion = $buzonService->sincronizarEmails();
            
            Log::info('DIAN Procesamiento: Resultado sincronización', $resultadosSincronizacion);
            
            if ($resultadosSincronizacion['success']) {
                // Paso 2: Procesar emails del buzón local
                $resultadosProcesamiento = $buzonService->procesarEmailsDelBuzon();
                
                Log::info('DIAN Procesamiento: Resultado procesamiento buzón', $resultadosProcesamiento);
                
                $mensaje = "Buzón sincronizado: {$resultadosSincronizacion['emails_descargados']} emails descargados, {$resultadosSincronizacion['emails_con_facturas']} con facturas.";
                
                if ($resultadosProcesamiento['success']) {
                    $mensaje .= " Procesados: {$resultadosProcesamiento['emails_procesados']} emails.";
                }
                
                return back()->with('success', $mensaje)
                            ->with('resultados_sincronizacion', $resultadosSincronizacion)
                            ->with('resultados_procesamiento', $resultadosProcesamiento);
            } else {
                return back()->with('error', $resultadosSincronizacion['message'])
                            ->with('resultados_sincronizacion', $resultadosSincronizacion);
            }

        } catch (\Exception $e) {
            Log::error('DIAN Procesamiento: Error procesando emails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error procesando emails: ' . $e->getMessage());
        }
    }

    /**
     * Subir archivo XML de factura manualmente
     */
    public function subirFacturaXML(Request $request)
    {
        try {
            $request->validate([
                'archivo_xml' => 'required|file|mimes:xml|max:2048'
            ]);

            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN activa');
            }

            Log::info('DIAN Subida: Procesando archivo XML subido', [
                'usuario_id' => auth()->id(),
                'empresa_id' => $empresa->id,
                'archivo' => $request->file('archivo_xml')->getClientOriginalName()
            ]);

            // Guardar archivo
            $archivo = $request->file('archivo_xml');
            $nombreArchivo = 'factura_' . time() . '_' . $archivo->getClientOriginalName();
            $rutaArchivo = $archivo->storeAs('dian/facturas', $nombreArchivo);

            // Procesar XML
            $xmlService = new \App\Services\Dian\XmlFacturaService();
            $datosFactura = $xmlService->procesarXML(storage_path('app/' . $rutaArchivo));

            if ($datosFactura) {
                // Crear factura en base de datos
                $factura = \App\Models\FacturaDianProcesada::create([
                    'empresa_id' => $empresa->id,
                    'mensaje_id' => 'MANUAL_' . uniqid() . '_' . time(),
                    'remitente_email' => 'subida_manual@sistema.local',
                    'remitente_nombre' => 'Subida Manual',
                    'asunto_email' => 'Factura XML subida manualmente: ' . $archivo->getClientOriginalName(),
                    'fecha_email' => now(),
                    'cufe' => $datosFactura['cufe'] ?? 'CUFE_' . uniqid(),
                    'numero_factura' => $datosFactura['numero_factura'] ?? 'MANUAL_' . time(),
                    'nit_emisor' => $datosFactura['nit_emisor'] ?? 'N/A',
                    'nombre_emisor' => $datosFactura['nombre_emisor'] ?? 'Emisor Manual',
                    'valor_total' => $datosFactura['valor_total'] ?? 0,
                    'fecha_factura' => $datosFactura['fecha_factura'] ?? now(),
                    'archivos_adjuntos' => json_encode([$nombreArchivo]),
                    'archivos_extraidos' => json_encode([$nombreArchivo]),
                    'ruta_xml' => $rutaArchivo,
                    'ruta_pdf' => null,
                    'estado' => 'procesada',
                    'detalles_procesamiento' => json_encode(['metodo' => 'subida_manual', 'fecha' => now()]),
                    'errores' => json_encode([]),
                    'acuse_enviado' => false,
                    'fecha_acuse' => null,
                    'id_acuse' => null,
                    'contenido_acuse' => null,
                    'intentos_procesamiento' => 1,
                    'ultimo_intento' => now(),
                    'metadatos_adicionales' => json_encode(['tipo' => 'subida_manual', 'archivo_original' => $archivo->getClientOriginalName()]),
                    'observaciones' => 'Factura XML subida manualmente por el usuario'
                ]);

                Log::info('DIAN Subida: Factura creada exitosamente', [
                    'factura_id' => $factura->id,
                    'numero_factura' => $factura->numero_factura,
                    'cufe' => $factura->cufe
                ]);

                return back()->with('success', 'Factura XML procesada exitosamente. Número: ' . $factura->numero_factura);
            } else {
                return back()->with('error', 'No se pudo procesar el archivo XML. Verifica que sea una factura electrónica válida.');
            }

        } catch (\Exception $e) {
            Log::error('DIAN Subida: Error procesando archivo XML', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error procesando archivo XML: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar vista dedicada para procesar emails
     */
    public function mostrarProcesarEmails()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            // Obtener estadísticas
            $estadisticas = [
                'nuevos' => EmailBuzon::where('empresa_id', $empresa->id)
                                     ->where('estado', 'nuevo')
                                     ->count(),
                'con_facturas' => EmailBuzon::where('empresa_id', $empresa->id)
                                           ->where('tiene_facturas', true)
                                           ->count(),
                'procesados' => EmailBuzon::where('empresa_id', $empresa->id)
                                         ->where('estado', 'procesado')
                                         ->count(),
            ];

            return view('dian.procesar-emails', compact('configuracion', 'estadisticas'));

        } catch (\Exception $e) {
            Log::error('Error mostrando vista procesar emails', [
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error cargando vista: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar vista dedicada para enviar acuses
     */
    public function mostrarEnviarAcuses()
    {
        try {
            $empresa = auth()->user()->empresa;
            
            // Obtener facturas pendientes de acuse
            $facturasPendientes = EmailBuzon::where('empresa_id', $empresa->id)
                                           ->where('tiene_facturas', true)
                                           ->whereNotNull('metadatos')
                                           ->get()
                                           ->filter(function($email) {
                                               $metadatos = is_string($email->metadatos) ? 
                                                          json_decode($email->metadatos, true) : 
                                                          ($email->metadatos ?? []);
                                               return !($metadatos['acuse_enviado'] ?? false);
                                           });

            $facturasPendientesPaginadas = new \Illuminate\Pagination\LengthAwarePaginator(
                $facturasPendientes->take(10),
                $facturasPendientes->count(),
                10,
                request()->get('page', 1),
                ['path' => request()->url()]
            );

            // Calcular estadísticas
            $totalFacturas = EmailBuzon::where('empresa_id', $empresa->id)
                                      ->where('tiene_facturas', true)
                                      ->count();

            $acusesEnviados = EmailBuzon::where('empresa_id', $empresa->id)
                                       ->where('tiene_facturas', true)
                                       ->whereNotNull('metadatos')
                                       ->get()
                                       ->filter(function($email) {
                                           $metadatos = is_string($email->metadatos) ? 
                                                      json_decode($email->metadatos, true) : 
                                                      ($email->metadatos ?? []);
                                           return $metadatos['acuse_enviado'] ?? false;
                                       })
                                       ->count();

            $estadisticas = [
                'total' => $totalFacturas,
                'pendientes' => $facturasPendientes->count(),
                'enviados' => $acusesEnviados,
                'porcentaje' => $totalFacturas > 0 ? round(($acusesEnviados / $totalFacturas) * 100, 1) : 0
            ];

            return view('dian.enviar-acuses', [
                'facturasPendientes' => $facturasPendientesPaginadas,
                'estadisticas' => $estadisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Error mostrando vista enviar acuses', [
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error cargando vista: ' . $e->getMessage());
        }
    }

    /**
     * Enviar acuses pendientes
     */
    public function enviarAcuses()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN activa');
            }

            $acuseGenerator = new AcuseGeneratorService($configuracion);
            $resultados = $acuseGenerator->enviarAcusesPendientes();
            
            $mensaje = "Acuses enviados: {$resultados['acuses_enviados']}";
            
            if (!empty($resultados['errores'])) {
                $mensaje .= ". Errores: " . count($resultados['errores']);
            }

            return redirect()->route('dian.enviar-acuses.vista')
                           ->with('success', $mensaje)
                           ->with('resultados_acuses', $resultados);

        } catch (\Exception $e) {
            Log::error('Error enviando acuses: ' . $e->getMessage());
            return back()->with('error', 'Error enviando acuses: ' . $e->getMessage());
        }
    }

    /**
     * Listar facturas procesadas
     */
    public function facturas(Request $request)
    {
        Log::info('DIAN Facturas: Acceso a lista de facturas', [
            'usuario_id' => auth()->id(),
            'filtros' => $request->only(['estado', 'fecha_desde', 'fecha_hasta', 'buscar'])
        ]);

        $empresa = auth()->user()->empresa;
        
        // Obtener estadísticas
        $estadisticas = [
            'total' => FacturaDianProcesada::where('empresa_id', $empresa->id)->count(),
            'procesadas' => FacturaDianProcesada::where('empresa_id', $empresa->id)->where('estado', 'procesada')->count(),
            'pendientes' => FacturaDianProcesada::where('empresa_id', $empresa->id)->where('estado', 'pendiente')->count(),
            'hoy' => FacturaDianProcesada::where('empresa_id', $empresa->id)->whereDate('created_at', today())->count()
        ];
        
        $query = FacturaDianProcesada::where('empresa_id', $empresa->id);
        
        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_email', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_email', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('cufe', 'like', "%{$buscar}%")
                  ->orWhere('numero_factura', 'like', "%{$buscar}%")
                  ->orWhere('nit_emisor', 'like', "%{$buscar}%")
                  ->orWhere('nombre_emisor', 'like', "%{$buscar}%")
                  ->orWhere('remitente_email', 'like', "%{$buscar}%")
                  ->orWhere('asunto_email', 'like', "%{$buscar}%");
            });
        }

        $facturas = $query->orderBy('fecha_email', 'desc')->paginate(20);
        
        Log::info('DIAN Facturas: Lista obtenida', [
            'total_facturas' => $facturas->total(),
            'facturas_pagina' => $facturas->count(),
            'estadisticas' => $estadisticas
        ]);
        
        return view('dian.facturas', compact('facturas', 'estadisticas'));
    }

    /**
     * Ver detalle de una factura
     */
    public function verFactura(FacturaDianProcesada $factura)
    {
        // Verificar que pertenece a la empresa del usuario
        if ($factura->empresa_id !== auth()->user()->empresa->id) {
            abort(403);
        }

        Log::info('DIAN Factura: Acceso a detalle de factura', [
            'factura_id' => $factura->id,
            'usuario_id' => auth()->id()
        ]);

        return view('dian.factura-detalle', compact('factura'));
    }

    /**
     * Obtener detalle de factura via AJAX
     */
    public function detalleFacturaAjax(FacturaDianProcesada $factura)
    {
        // Verificar que pertenece a la empresa del usuario
        if ($factura->empresa_id !== auth()->user()->empresa->id) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado']);
        }

        Log::info('DIAN Factura: Detalle AJAX solicitado', [
            'factura_id' => $factura->id,
            'usuario_id' => auth()->id()
        ]);

        $html = view('dian.partials.factura-detalle-modal', compact('factura'))->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * Descargar XML de factura
     */
    public function descargarXML(FacturaDianProcesada $factura)
    {
        // Verificar que pertenece a la empresa del usuario
        if ($factura->empresa_id !== auth()->user()->empresa->id) {
            abort(403);
        }

        if (!$factura->archivo_xml || !file_exists(storage_path('app/' . $factura->archivo_xml))) {
            abort(404, 'Archivo XML no encontrado');
        }

        Log::info('DIAN Factura: Descarga de XML', [
            'factura_id' => $factura->id,
            'archivo_xml' => $factura->archivo_xml,
            'usuario_id' => auth()->id()
        ]);

        return response()->download(
            storage_path('app/' . $factura->archivo_xml),
            'factura_' . $factura->numero_factura . '.xml'
        );
    }

    /**
     * Enviar acuse individual
     */
    public function enviarAcuseIndividual(FacturaDianProcesada $factura)
    {
        try {
            // Verificar que pertenece a la empresa del usuario
            if ($factura->empresa_id !== auth()->user()->empresa->id) {
                return response()->json(['success' => false, 'message' => 'Acceso denegado']);
            }

            if ($factura->acuse_enviado) {
                return response()->json(['success' => false, 'message' => 'El acuse ya fue enviado']);
            }

            Log::info('DIAN Acuse: Enviando acuse individual', [
                'factura_id' => $factura->id,
                'usuario_id' => auth()->id()
            ]);

            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);

            if (!$configuracion) {
                return response()->json(['success' => false, 'message' => 'No hay configuración DIAN activa']);
            }

            $acuseGenerator = new \App\Services\Dian\AcuseGeneratorService($configuracion);
            $resultado = $acuseGenerator->enviarAcuseIndividual($factura);

            if ($resultado['success']) {
                $factura->update([
                    'acuse_enviado' => true,
                    'fecha_acuse' => now()
                ]);

                Log::info('DIAN Acuse: Acuse enviado exitosamente', [
                    'factura_id' => $factura->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Acuse enviado correctamente'
                ]);
            } else {
                Log::error('DIAN Acuse: Error enviando acuse', [
                    'factura_id' => $factura->id,
                    'error' => $resultado['message']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error enviando acuse: ' . $resultado['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('DIAN Acuse: Excepción enviando acuse individual', [
                'factura_id' => $factura->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error enviando acuse: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Activar/Desactivar módulo
     */
    public function toggleActivacion()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN');
            }

            $configuracion->update(['activo' => !$configuracion->activo]);
            
            $estado = $configuracion->activo ? 'activado' : 'desactivado';
            
            return back()->with('success', "Módulo DIAN {$estado} exitosamente");

        } catch (\Exception $e) {
            return back()->with('error', 'Error cambiando estado: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas del módulo usando EmailBuzon
     */
    private function obtenerEstadisticas($empresaId): array
    {
        // Usar EmailBuzon en lugar de FacturaDianProcesada
        $emails = EmailBuzon::where('empresa_id', $empresaId);
        $emailsConFacturas = EmailBuzon::where('empresa_id', $empresaId)->where('tiene_facturas', true);
        
        // Contar acuses enviados
        $acusesEnviados = EmailBuzon::where('empresa_id', $empresaId)
                                  ->where('tiene_facturas', true)
                                  ->whereNotNull('metadatos')
                                  ->get()
                                  ->filter(function($email) {
                                      $metadatos = is_string($email->metadatos) ? 
                                                  json_decode($email->metadatos, true) : 
                                                  ($email->metadatos ?? []);
                                      return $metadatos['acuse_enviado'] ?? false;
                                  })
                                  ->count();
        
        return [
            'total_facturas' => $emailsConFacturas->count(),
            'facturas_hoy' => $emailsConFacturas->whereDate('fecha_email', today())->count(),
            'facturas_mes' => $emailsConFacturas->whereMonth('fecha_email', now()->month)->count(),
            'acuses_enviados' => $acusesEnviados,
            'pendientes_acuse' => $emailsConFacturas->count() - $acusesEnviados,
            'con_errores' => $emails->where('estado', 'error')->count(),
            'valor_total_mes' => 0, // No tenemos valor en EmailBuzon
            'ultimo_procesamiento' => $emails->orderBy('updated_at', 'desc')
                                            ->first()?->updated_at
        ];
    }

    /**
     * Probar conexión IMAP simplificada
     */
    private function probarConexionIMAP(ConfiguracionDian $config): array
    {
        try {
            $servidor = "{{$config->servidor_imap}:{$config->puerto_imap}";
            
            if ($config->ssl_enabled) {
                $servidor .= "/imap/ssl";
            }
            
            $servidor .= "}INBOX";

            $conexion = @imap_open(
                $servidor,
                $config->email_dian,
                $config->password_email
            );

            if ($conexion) {
                $info = imap_status($conexion, $servidor, SA_ALL);
                imap_close($conexion);
                
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa',
                    'detalles' => [
                        'mensajes' => $info->messages ?? 0,
                        'no_leidos' => $info->unseen ?? 0
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error de conexión: ' . imap_last_error()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Iniciar autorización OAuth2 para Gmail
     */
    public function iniciarAutorizacionOAuth()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN activa');
            }

            $gmailService = new \App\Services\Dian\GmailRealApiService($configuracion->email_dian);
            $authUrl = $gmailService->getAuthUrl();

            Log::info('DIAN OAuth: Iniciando autorización', [
                'usuario_id' => auth()->id(),
                'email' => $configuracion->email_dian
            ]);

            return redirect($authUrl);

        } catch (\Exception $e) {
            Log::error('DIAN OAuth: Error iniciando autorización', [
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error iniciando autorización: ' . $e->getMessage());
        }
    }

    /**
     * Callback OAuth2 de Gmail
     */
    public function callbackOAuth(Request $request)
    {
        try {
            $code = $request->get('code');
            
            if (!$code) {
                return redirect()->route('dian.dashboard')->with('error', 'Autorización cancelada');
            }

            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return redirect()->route('dian.dashboard')->with('error', 'No hay configuración DIAN activa');
            }

            $gmailService = new \App\Services\Dian\GmailRealApiService($configuracion->email_dian);
            $success = $gmailService->processAuthCode($code);

            if ($success) {
                Log::info('DIAN OAuth: Autorización exitosa', [
                    'usuario_id' => auth()->id(),
                    'email' => $configuracion->email_dian
                ]);

                return redirect()->route('dian.dashboard')->with('success', '¡Autorización exitosa! Ahora puedes procesar emails reales de Gmail.');
            } else {
                return redirect()->route('dian.dashboard')->with('error', 'Error en la autorización. Inténtalo de nuevo.');
            }

        } catch (\Exception $e) {
            Log::error('DIAN OAuth: Error en callback', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('dian.dashboard')->with('error', 'Error en autorización: ' . $e->getMessage());
        }
    }

    /**
     * Ver buzón de correos
     */
    public function verBuzon()
    {
        try {
            $empresa = auth()->user()->empresa;
            $configuracion = ConfiguracionDian::getConfiguracionActiva($empresa->id);
            
            if (!$configuracion) {
                return back()->with('error', 'No hay configuración DIAN activa');
            }

            // Obtener emails del buzón
            $emails = \App\Models\EmailBuzon::porEmpresa($empresa->id)
                ->orderBy('fecha_email', 'desc')
                ->paginate(20);

            // Obtener estadísticas
            $buzonService = new \App\Services\Dian\BuzonEmailService($configuracion);
            $estadisticas = $buzonService->obtenerEstadisticas();

            return view('dian.buzon', compact('emails', 'estadisticas', 'configuracion'));

        } catch (\Exception $e) {
            Log::error('DIAN Buzón: Error mostrando buzón', [
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error mostrando buzón: ' . $e->getMessage());
        }
    }
}

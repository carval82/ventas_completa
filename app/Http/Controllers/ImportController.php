<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\StockUbicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function showImportForm()
    {
        return view('import.form');
    }

    public function importInventario(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx'
        ]);

        try {
            ini_set('memory_limit', '512M');
            
            DB::beginTransaction();
            
            // Obtener la bodega principal
            $bodega = Ubicacion::where('tipo', 'bodega')
                ->where('estado', true)
                ->firstOrFail();

            Log::info('Iniciando importación de inventario');

            // Leer el archivo Excel
            $inputFileName = $request->file('file')->getRealPath();
            Log::info('Leyendo archivo:', ['path' => $inputFileName]);

            // Crear el lector
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);

            // Verificar si el archivo tiene hojas
            if (!$reader->canRead($inputFileName)) {
                throw new \Exception('El archivo no es un archivo Excel válido.');
            }

            // Cargar el libro
            $spreadsheet = $reader->load($inputFileName);

            // Verificar si hay hojas disponibles
            if ($spreadsheet->getSheetCount() === 0) {
                throw new \Exception('El archivo Excel no contiene hojas.');
            }

            // Obtener la primera hoja
            $worksheet = $spreadsheet->getSheet(0);
            Log::info('Hoja cargada:', ['nombre' => $worksheet->getTitle()]);

            $productos_creados = 0;
            $productos_actualizados = 0;
            $errores = [];

            // Obtener el rango de filas con datos
            $highestRow = $worksheet->getHighestRow();
            Log::info('Número de filas encontradas:', ['rows' => $highestRow]);

            // Procesar por lotes
            $batchSize = 100;
            
            // Saltar la primera fila (encabezados)
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    // Leer datos de la fila
                    $codigo = trim($worksheet->getCell("A{$row}")->getValue());
                    
                    // Saltar filas vacías
                    if (empty($codigo)) {
                        continue;
                    }

                    $nombre = trim($worksheet->getCell("B{$row}")->getValue());
                    $descripcion = trim($worksheet->getCell("C{$row}")->getValue() ?? $nombre);
                    $precio_compra = $this->convertirNumero($worksheet->getCell("D{$row}")->getValue());
                    $precio_venta = $this->convertirNumero($worksheet->getCell("E{$row}")->getValue());
                    $stock_minimo = (int)$worksheet->getCell("F{$row}")->getValue();
                    $stock = (int)$worksheet->getCell("G{$row}")->getValue();

                    // Validar datos mínimos
                    if (empty($nombre)) {
                        throw new \Exception("El nombre es requerido");
                    }

                    Log::info("Procesando producto", [
                        'fila' => $row,
                        'codigo' => $codigo,
                        'nombre' => $nombre
                    ]);

                    // Crear o actualizar producto
                    $producto = Producto::updateOrCreate(
                        ['codigo' => $codigo],
                        [
                            'nombre' => $nombre,
                            'descripcion' => $descripcion,
                            'precio_compra' => $precio_compra,
                            'precio_venta' => $precio_venta,
                            'stock_minimo' => $stock_minimo,
                            'stock' => $stock,
                            'estado' => true
                        ]
                    );

                    // Actualizar stock en ubicación
                    StockUbicacion::updateOrCreate(
                        [
                            'producto_id' => $producto->id,
                            'ubicacion_id' => $bodega->id
                        ],
                        ['stock' => $stock]
                    );

                    if ($producto->wasRecentlyCreated) {
                        $productos_creados++;
                    } else {
                        $productos_actualizados++;
                    }

                    // Liberar memoria cada cierto número de registros
                    if ($row % $batchSize === 0) {
                        DB::commit();
                        DB::beginTransaction();
                        gc_collect_cycles();
                    }

                } catch (\Exception $e) {
                    $errores[] = "Error en fila $row: " . $e->getMessage();
                    Log::error("Error procesando fila", [
                        'fila' => $row,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();
            
            // Liberar memoria
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();

            Log::info('Importación completada', [
                'creados' => $productos_creados,
                'actualizados' => $productos_actualizados,
                'errores' => count($errores)
            ]);

            return redirect()->back()->with([
                'success' => "Importación completada. Productos creados: $productos_creados, actualizados: $productos_actualizados",
                'errores' => $errores
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error general en importación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error en la importación: ' . $e->getMessage())
                ->withErrors(['file' => $e->getMessage()]);
        }
    }

    private function convertirNumero($valor)
    {
        if (empty($valor)) return 0;
        
        if (is_string($valor)) {
            $valor = str_replace(['$', ',', ' '], '', $valor);
        }
        
        return floatval($valor);
    }
}
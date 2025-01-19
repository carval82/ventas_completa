<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportTercerosController extends Controller
{
    public function showImportForm()
    {
        return view('import.terceros');
    }

    public function importTerceros(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
            'tipo' => 'required|in:clientes,proveedores'
        ]);

        try {
            ini_set('memory_limit', '512M');
            
            DB::beginTransaction();

            Log::info('Iniciando importación de ' . $request->tipo);

            $inputFileName = $request->file('file')->getRealPath();
            Log::info('Leyendo archivo:', ['path' => $inputFileName]);

            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);

            if (!$reader->canRead($inputFileName)) {
                throw new \Exception('El archivo no es un archivo Excel válido.');
            }

            $spreadsheet = $reader->load($inputFileName);

            if ($spreadsheet->getSheetCount() === 0) {
                throw new \Exception('El archivo Excel no contiene hojas.');
            }

            $worksheet = $spreadsheet->getSheet(0);
            Log::info('Hoja cargada:', ['nombre' => $worksheet->getTitle()]);

            $creados = 0;
            $actualizados = 0;
            $errores = [];

            $highestRow = $worksheet->getHighestRow();
            Log::info('Número de filas encontradas:', ['rows' => $highestRow]);

            $batchSize = 100;
            
            // Saltar la primera fila (encabezados)
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    if ($request->tipo === 'clientes') {
                        $identificacion = trim($worksheet->getCell("A{$row}")->getValue());
                        if (empty($identificacion)) continue;

                        $nombres = trim($worksheet->getCell("B{$row}")->getValue());
                        $apellidos = trim($worksheet->getCell("C{$row}")->getValue());
                        $telefono = trim($worksheet->getCell("D{$row}")->getValue());
                        $email = trim($worksheet->getCell("E{$row}")->getValue());
                        $direccion = trim($worksheet->getCell("F{$row}")->getValue());

                        if (empty($nombres)) {
                            throw new \Exception("El nombre es requerido");
                        }

                        $tercero = Cliente::updateOrCreate(
                            ['cedula' => $identificacion],
                            [
                                'nombres' => $nombres,
                                'apellidos' => $apellidos,
                                'telefono' => $telefono,
                                'email' => $email,
                                'direccion' => $direccion,
                                'estado' => true
                            ]
                        );
                    } else {
                        $nit = trim($worksheet->getCell("A{$row}")->getValue());
                        if (empty($nit)) continue;

                        $nombre = trim($worksheet->getCell("B{$row}")->getValue());
                        $telefono = trim($worksheet->getCell("C{$row}")->getValue());
                        $email = trim($worksheet->getCell("D{$row}")->getValue());
                        $direccion = trim($worksheet->getCell("E{$row}")->getValue());

                        if (empty($nombre)) {
                            throw new \Exception("El nombre es requerido");
                        }

                        $tercero = Proveedor::updateOrCreate(
                            ['nit' => $nit],
                            [
                                'nombre' => $nombre,
                                'telefono' => $telefono,
                                'email' => $email,
                                'direccion' => $direccion,
                                'estado' => true
                            ]
                        );
                    }

                    if ($tercero->wasRecentlyCreated) {
                        $creados++;
                    } else {
                        $actualizados++;
                    }

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
            
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();

            Log::info('Importación completada', [
                'creados' => $creados,
                'actualizados' => $actualizados,
                'errores' => count($errores)
            ]);

            return redirect()->back()->with([
                'success' => "Importación completada. Registros creados: $creados, actualizados: $actualizados",
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
}
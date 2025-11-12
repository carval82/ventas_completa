<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model 
{
   protected $fillable = [
       'nombres', 'apellidos', 'cedula', 'telefono',
       'email', 'direccion', 'ciudad', 'departamento', 
       'tipo_documento', 'tipo_persona', 'regimen', 'estado', 'id_alegra'
   ];

   public function ventas()
   {
       return $this->hasMany(Venta::class);
   }

   /**
    * Sincroniza el cliente con Alegra
    * Si el cliente ya tiene un id_alegra, se actualiza
    * Si no, se crea un nuevo cliente en Alegra
    * 
    * @return array Resultado de la operación
    */
   public function syncToAlegra()
   {
       try {
           // Si ya tiene un ID de Alegra, no es necesario sincronizar
           if ($this->id_alegra) {
               return [
                   'success' => true,
                   'message' => 'Cliente ya sincronizado con Alegra',
                   'id_alegra' => $this->id_alegra
               ];
           }

           // Obtener servicio de Alegra
           $alegraService = app(\App\Http\Services\AlegraService::class);
           
           // Primero buscar si el cliente ya existe en Alegra por su identificación
           $clientesAlegra = $alegraService->obtenerClientes();
           
           if ($clientesAlegra['success']) {
               foreach ($clientesAlegra['data'] as $clienteAlegra) {
                   // Verificar si existe un cliente con la misma identificación
                   if (isset($clienteAlegra['identification']) && $clienteAlegra['identification'] == $this->cedula) {
                       // Guardar el ID de Alegra en el cliente
                       $this->id_alegra = $clienteAlegra['id'];
                       $this->save();
                       
                       \Log::info('Cliente encontrado en Alegra', [
                           'cliente_id' => $this->id,
                           'alegra_id' => $this->id_alegra
                       ]);
                       
                       return [
                           'success' => true,
                           'message' => 'Cliente encontrado y vinculado con Alegra',
                           'id_alegra' => $this->id_alegra
                       ];
                   }
               }
           }
           
           // Si no existe, crear cliente en Alegra
           $result = $alegraService->crearClienteAlegra($this);
           
           if ($result['success']) {
               // Guardar el ID de Alegra en el cliente
               $this->id_alegra = $result['data']['id'];
               $this->save();
               
               return [
                   'success' => true,
                   'message' => 'Cliente sincronizado con Alegra',
                   'id_alegra' => $this->id_alegra
               ];
           }
           
           return [
               'success' => false,
               'message' => 'Error al sincronizar cliente con Alegra',
               'error' => $result['error'] ?? 'Error desconocido'
           ];
       } catch (\Exception $e) {
           \Log::error('Error al sincronizar cliente con Alegra', [
               'cliente_id' => $this->id,
               'error' => $e->getMessage()
           ]);
           
           return [
               'success' => false,
               'message' => 'Error al sincronizar cliente con Alegra',
               'error' => $e->getMessage()
           ];
       }
   }
}
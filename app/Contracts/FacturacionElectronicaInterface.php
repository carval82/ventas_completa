<?php

namespace App\Contracts;

interface FacturacionElectronicaInterface
{
    /**
     * Enviar factura electrónica
     *
     * @param array $facturaData
     * @return array
     */
    public function enviarFactura(array $facturaData): array;

    /**
     * Consultar estado de factura
     *
     * @param string $identificador
     * @return array
     */
    public function consultarEstado(string $identificador): array;

    /**
     * Obtener configuración del proveedor
     *
     * @return array
     */
    public function getConfiguracion(): array;

    /**
     * Validar configuración del proveedor
     *
     * @return bool
     */
    public function validarConfiguracion(): bool;

    /**
     * Obtener nombre del proveedor
     *
     * @return string
     */
    public function getNombreProveedor(): string;

    /**
     * Sincronizar productos (opcional)
     *
     * @param array $productos
     * @return array
     */
    public function sincronizarProductos(array $productos): array;

    /**
     * Sincronizar clientes (opcional)
     *
     * @param array $clientes
     * @return array
     */
    public function sincronizarClientes(array $clientes): array;
}

<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class ReporteFiscalIvaExport
{
    protected $data;
    protected $fechaInicio;
    protected $fechaFin;

    /**
     * Constructor
     * 
     * @param array $data Datos del reporte fiscal
     * @param string $fechaInicio Fecha de inicio del período
     * @param string $fechaFin Fecha de fin del período
     */
    public function __construct(array $data, string $fechaInicio, string $fechaFin)
    {
        $this->data = $data;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $collection = new Collection();
        
        // Información general
        $collection->push([
            'REPORTE FISCAL DE IVA',
            'Período: ' . date('d/m/Y', strtotime($this->fechaInicio)) . ' - ' . date('d/m/Y', strtotime($this->fechaFin)),
            '',
            ''
        ]);
        
        $collection->push(['', '', '', '']);
        
        // Resumen de IVA
        $collection->push([
            'RESUMEN DE IVA',
            '',
            '',
            ''
        ]);
        
        $collection->push([
            'IVA Generado (Ventas)',
            number_format($this->data['iva_generado'], 2),
            '',
            ''
        ]);
        
        $collection->push([
            'IVA Descontable (Compras)',
            number_format($this->data['iva_descontable'], 2),
            '',
            ''
        ]);
        
        $collection->push([
            'Saldo a Pagar',
            $this->data['saldo_a_pagar'] > 0 ? number_format($this->data['saldo_a_pagar'], 2) : '0.00',
            '',
            ''
        ]);
        
        $collection->push([
            'Saldo a Favor',
            $this->data['saldo_a_favor'] > 0 ? number_format($this->data['saldo_a_favor'], 2) : '0.00',
            '',
            ''
        ]);
        
        $collection->push(['', '', '', '']);
        
        // Detalle de ventas
        $collection->push([
            'DETALLE DE VENTAS CON IVA',
            '',
            '',
            ''
        ]);
        
        $collection->push([
            'Fecha',
            'Factura',
            'Cliente',
            'Subtotal',
            'IVA',
            'Total'
        ]);
        
        foreach ($this->data['ventas']['detalle'] as $venta) {
            $collection->push([
                date('d/m/Y', strtotime($venta['fecha'])),
                $venta['numero_factura'],
                $venta['cliente'],
                number_format($venta['subtotal'], 2),
                number_format($venta['iva'], 2),
                number_format($venta['total'], 2)
            ]);
        }
        
        $collection->push([
            'TOTAL',
            '',
            '',
            number_format($this->data['ventas']['total_ventas'], 2),
            number_format($this->data['ventas']['total_iva'], 2),
            number_format($this->data['ventas']['total_general'], 2)
        ]);
        
        $collection->push(['', '', '', '']);
        
        // Detalle de compras
        $collection->push([
            'DETALLE DE COMPRAS CON IVA',
            '',
            '',
            ''
        ]);
        
        $collection->push([
            'Fecha',
            'Factura',
            'Proveedor',
            'Subtotal',
            'IVA',
            'Total'
        ]);
        
        foreach ($this->data['compras']['detalle'] as $compra) {
            $collection->push([
                date('d/m/Y', strtotime($compra['fecha'])),
                $compra['numero_factura'],
                $compra['proveedor'],
                number_format($compra['subtotal'], 2),
                number_format($compra['iva'], 2),
                number_format($compra['total'], 2)
            ]);
        }
        
        $collection->push([
            'TOTAL',
            '',
            '',
            number_format($this->data['compras']['total_compras'], 2),
            number_format($this->data['compras']['total_iva'], 2),
            number_format($this->data['compras']['total_general'], 2)
        ]);
        
        return $collection;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // No usamos encabezados tradicionales porque tenemos múltiples secciones
        return [];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Reporte Fiscal IVA';
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Estilos para títulos
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A10')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A18')->getFont()->setBold(true)->setSize(12);
        
        // Estilos para encabezados de tablas
        $sheet->getStyle('A11:F11')->getFont()->setBold(true);
        $sheet->getStyle('A19:F19')->getFont()->setBold(true);
        
        // Estilos para totales
        $sheet->getStyle('A' . (11 + count($this->data['ventas']['detalle']) + 1))->getFont()->setBold(true);
        $sheet->getStyle('A' . (19 + count($this->data['compras']['detalle']) + 1))->getFont()->setBold(true);
        
        // Formato de moneda para columnas de valores
        $lastRowVentas = 11 + count($this->data['ventas']['detalle']) + 1;
        $lastRowCompras = 19 + count($this->data['compras']['detalle']) + 1;
        
        $sheet->getStyle('D12:F' . $lastRowVentas)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D20:F' . $lastRowCompras)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('B4:B7')->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
    }
}

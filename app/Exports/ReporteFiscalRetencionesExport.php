<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReporteFiscalRetencionesExport implements WithMultipleSheets
{
    protected $reporte;
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($reporte, $fechaInicio, $fechaFin)
    {
        $this->reporte = $reporte;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [
            new ResumenRetencionesSheet($this->reporte, $this->fechaInicio, $this->fechaFin),
            new RetencionesEfectuadasSheet($this->reporte['retenciones_efectuadas'], $this->fechaInicio, $this->fechaFin),
            new RetencionesPracticadasSheet($this->reporte['retenciones_practicadas'], $this->fechaInicio, $this->fechaFin),
        ];

        return $sheets;
    }
}

class ResumenRetencionesSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
{
    protected $reporte;
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($reporte, $fechaInicio, $fechaFin)
    {
        $this->reporte = $reporte;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function collection()
    {
        $data = new Collection();
        
        // Datos de retenciones efectuadas por tipo
        if (!empty($this->reporte['retenciones_efectuadas']['por_tipo'])) {
            foreach ($this->reporte['retenciones_efectuadas']['por_tipo'] as $tipo) {
                $data->push([
                    'Retenciones Efectuadas',
                    $tipo->tipo,
                    number_format($tipo->total_base, 2),
                    number_format($tipo->total_retenido, 2),
                    $tipo->cantidad
                ]);
            }
        } else {
            $data->push([
                'Retenciones Efectuadas',
                'N/A',
                '0.00',
                '0.00',
                0
            ]);
        }
        
        // Línea en blanco
        $data->push(['', '', '', '', '']);
        
        // Datos de retenciones practicadas por tipo
        if (!empty($this->reporte['retenciones_practicadas']['por_tipo'])) {
            foreach ($this->reporte['retenciones_practicadas']['por_tipo'] as $tipo) {
                $data->push([
                    'Retenciones Practicadas',
                    $tipo->tipo,
                    number_format($tipo->total_base, 2),
                    number_format($tipo->total_retenido, 2),
                    $tipo->cantidad
                ]);
            }
        } else {
            $data->push([
                'Retenciones Practicadas',
                'N/A',
                '0.00',
                '0.00',
                0
            ]);
        }
        
        // Línea en blanco
        $data->push(['', '', '', '', '']);
        
        // Totales y saldos
        $data->push([
            'TOTALES',
            '',
            '',
            number_format($this->reporte['total_efectuadas'], 2),
            ''
        ]);
        
        $data->push([
            'SALDO A PAGAR',
            '',
            '',
            number_format($this->reporte['saldo_a_pagar'], 2),
            ''
        ]);
        
        return $data;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        $fechaInicio = Carbon::parse($this->fechaInicio)->format('d/m/Y');
        $fechaFin = Carbon::parse($this->fechaFin)->format('d/m/Y');
        
        return [
            ['REPORTE FISCAL DE RETENCIONES'],
            ['Período: ' . $fechaInicio . ' - ' . $fechaFin],
            [''],
            ['Tipo', 'Concepto', 'Base', 'Valor Retenido', 'Cantidad']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DDDDDD']]],
            'A' => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 25,
            'C' => 15,
            'D' => 15,
            'E' => 10,
        ];
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:E1')->getFont()->setSize(16);
                $event->sheet->getDelegate()->getStyle('A4:E4')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A4:E4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
                
                // Formato para los totales
                $lastRow = $event->sheet->getDelegate()->getHighestRow();
                $event->sheet->getDelegate()->getStyle('A' . ($lastRow-1) . ':E' . $lastRow)->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A' . ($lastRow-1) . ':E' . $lastRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');
            },
        ];
    }
}

class RetencionesEfectuadasSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
{
    protected $retencionesEfectuadas;
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($retencionesEfectuadas, $fechaInicio, $fechaFin)
    {
        $this->retencionesEfectuadas = $retencionesEfectuadas;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function collection()
    {
        $data = new Collection();
        
        if (!empty($this->retencionesEfectuadas['detalle'])) {
            foreach ($this->retencionesEfectuadas['detalle'] as $retencion) {
                $data->push([
                    Carbon::parse($retencion->fecha)->format('d/m/Y'),
                    $retencion->numero_factura,
                    $retencion->cliente,
                    $retencion->nit,
                    $retencion->tipo,
                    number_format($retencion->porcentaje, 2) . '%',
                    number_format($retencion->base, 2),
                    number_format($retencion->valor, 2),
                    $retencion->numero_certificado ?? 'N/A'
                ]);
            }
        }
        
        return $data;
    }

    public function title(): string
    {
        return 'Retenciones Efectuadas';
    }

    public function headings(): array
    {
        $fechaInicio = Carbon::parse($this->fechaInicio)->format('d/m/Y');
        $fechaFin = Carbon::parse($this->fechaFin)->format('d/m/Y');
        
        return [
            ['DETALLE DE RETENCIONES EFECTUADAS'],
            ['Período: ' . $fechaInicio . ' - ' . $fechaFin],
            [''],
            ['Fecha', 'Factura', 'Cliente', 'NIT', 'Tipo', 'Porcentaje', 'Base', 'Valor Retenido', 'Certificado']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DDDDDD']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 12,
            'C' => 30,
            'D' => 15,
            'E' => 15,
            'F' => 12,
            'G' => 15,
            'H' => 15,
            'I' => 15,
        ];
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:I1')->getFont()->setSize(16);
                $event->sheet->getDelegate()->getStyle('A4:I4')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A4:I4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
                
                // Formato para los totales
                $lastRow = $event->sheet->getDelegate()->getHighestRow();
                if ($lastRow > 4) {
                    $event->sheet->getDelegate()->setCellValue('A' . ($lastRow + 2), 'TOTAL');
                    $event->sheet->getDelegate()->setCellValue('G' . ($lastRow + 2), '=SUM(G5:G' . $lastRow . ')');
                    $event->sheet->getDelegate()->setCellValue('H' . ($lastRow + 2), '=SUM(H5:H' . $lastRow . ')');
                    $event->sheet->getDelegate()->getStyle('A' . ($lastRow + 2) . ':I' . ($lastRow + 2))->getFont()->setBold(true);
                    $event->sheet->getDelegate()->getStyle('A' . ($lastRow + 2) . ':I' . ($lastRow + 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');
                }
            },
        ];
    }
}

class RetencionesPracticadasSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
{
    protected $retencionesPracticadas;
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($retencionesPracticadas, $fechaInicio, $fechaFin)
    {
        $this->retencionesPracticadas = $retencionesPracticadas;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function collection()
    {
        $data = new Collection();
        
        if (!empty($this->retencionesPracticadas['detalle'])) {
            foreach ($this->retencionesPracticadas['detalle'] as $retencion) {
                $data->push([
                    Carbon::parse($retencion->fecha)->format('d/m/Y'),
                    $retencion->numero_factura,
                    $retencion->proveedor,
                    $retencion->nit,
                    $retencion->tipo,
                    number_format($retencion->porcentaje, 2) . '%',
                    number_format($retencion->base, 2),
                    number_format($retencion->valor, 2),
                    $retencion->numero_certificado ?? 'N/A'
                ]);
            }
        }
        
        return $data;
    }

    public function title(): string
    {
        return 'Retenciones Practicadas';
    }

    public function headings(): array
    {
        $fechaInicio = Carbon::parse($this->fechaInicio)->format('d/m/Y');
        $fechaFin = Carbon::parse($this->fechaFin)->format('d/m/Y');
        
        return [
            ['DETALLE DE RETENCIONES PRACTICADAS'],
            ['Período: ' . $fechaInicio . ' - ' . $fechaFin],
            [''],
            ['Fecha', 'Factura', 'Proveedor', 'NIT', 'Tipo', 'Porcentaje', 'Base', 'Valor Retenido', 'Certificado']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DDDDDD']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 12,
            'C' => 30,
            'D' => 15,
            'E' => 15,
            'F' => 12,
            'G' => 15,
            'H' => 15,
            'I' => 15,
        ];
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:I1')->getFont()->setSize(16);
                $event->sheet->getDelegate()->getStyle('A4:I4')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A4:I4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
                
                // Formato para los totales
                $lastRow = $event->sheet->getDelegate()->getHighestRow();
                if ($lastRow > 4) {
                    $event->sheet->getDelegate()->setCellValue('A' . ($lastRow + 2), 'TOTAL');
                    $event->sheet->getDelegate()->setCellValue('G' . ($lastRow + 2), '=SUM(G5:G' . $lastRow . ')');
                    $event->sheet->getDelegate()->setCellValue('H' . ($lastRow + 2), '=SUM(H5:H' . $lastRow . ')');
                    $event->sheet->getDelegate()->getStyle('A' . ($lastRow + 2) . ':I' . ($lastRow + 2))->getFont()->setBold(true);
                    $event->sheet->getDelegate()->getStyle('A' . ($lastRow + 2) . ':I' . ($lastRow + 2))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');
                }
            },
        ];
    }
}

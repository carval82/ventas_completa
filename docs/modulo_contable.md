# Documentación Técnica - Módulo Contable

## Introducción

Este documento describe la arquitectura, componentes y funcionamiento del módulo contable del sistema de ventas. El módulo ha sido diseñado siguiendo principios de código limpio, separación de responsabilidades y optimización de rendimiento.

## Arquitectura

El módulo contable está estructurado en capas:

1. **Controladores**: Manejan las peticiones HTTP y delegan la lógica de negocio a los servicios.
2. **Servicios**: Contienen la lógica de negocio y orquestan las operaciones contables.
3. **Modelos**: Representan las entidades de la base de datos y sus relaciones.
4. **Vistas**: Presentan la información al usuario final.

## Componentes Principales

### 1. Servicios

#### 1.1 ContabilidadService

Responsable de la generación y gestión de comprobantes contables.

**Métodos principales:**
- `generarComprobanteVenta($venta)`: Genera un comprobante contable a partir de una venta.
- `generarComprobanteCompra($compra)`: Genera un comprobante contable a partir de una compra.
- `generarComprobante($datos)`: Crea un comprobante contable con sus movimientos.
- `validarCuadreContable($movimientos)`: Verifica que los débitos y créditos estén balanceados.

#### 1.2 ContabilidadQueryService

Optimiza las consultas contables para reportes y análisis.

**Métodos principales:**
- `obtenerMovimientosCuenta($cuentaId, $fechaInicio, $fechaFin)`: Obtiene los movimientos de una cuenta en un período.
- `obtenerSaldoCuenta($cuentaId, $fecha)`: Calcula el saldo de una cuenta a una fecha específica (con caché).
- `generarBalanceComprobacion($fechaInicio, $fechaFin)`: Genera un balance de comprobación para un período.
- `obtenerResumenVentasConIva($fechaInicio, $fechaFin)`: Obtiene un resumen de ventas con IVA.
- `obtenerResumenComprasConIva($fechaInicio, $fechaFin)`: Obtiene un resumen de compras con IVA.
- `generarReporteFiscalIva($fechaInicio, $fechaFin)`: Genera un reporte fiscal de IVA.

#### 1.3 PlantillaComprobanteService

Gestiona las plantillas para la generación automática de comprobantes.

**Métodos principales:**
- `obtenerPlantillaVenta($datos)`: Obtiene la plantilla de movimientos para una venta.
- `obtenerPlantillaCompra($datos)`: Obtiene la plantilla de movimientos para una compra.
- `validarDatosPlantilla($datos)`: Valida que los datos necesarios para la plantilla estén completos.

#### 1.4 IvaValidationService

Centraliza la validación y cálculo de IVA.

**Métodos principales:**
- `validarPorcentajeIva($porcentaje)`: Valida que el porcentaje de IVA sea válido.
- `calcularIva($base, $porcentaje)`: Calcula el valor del IVA.
- `verificarCalculoIva($base, $porcentaje, $valorIva)`: Verifica que el cálculo de IVA sea correcto.
- `validarIva($base, $porcentaje, $valorIva)`: Realiza una validación completa del IVA.

### 2. Controladores

#### 2.1 ReporteContableController

Gestiona los reportes contables y fiscales.

**Métodos principales:**
- `balance_general($request)`: Genera un balance general a una fecha de corte.
- `estado_resultados($request)`: Genera un estado de resultados para un período.
- `libro_diario($request)`: Genera un libro diario para un período.
- `libro_mayor($request)`: Genera un libro mayor para una cuenta y período.
- `reporte_fiscal_iva($request)`: Genera un reporte fiscal de IVA para un período.

### 3. Modelos

#### 3.1 PlanCuenta

Representa las cuentas contables del sistema.

**Relaciones principales:**
- `movimientos()`: Relación con los movimientos contables.
- `subcuentas()`: Relación con las subcuentas (estructura jerárquica).

#### 3.2 Comprobante

Representa los comprobantes contables.

**Relaciones principales:**
- `movimientos()`: Relación con los movimientos del comprobante.
- `creadoPor()`: Relación con el usuario que creó el comprobante.
- `aprobadoPor()`: Relación con el usuario que aprobó el comprobante.

#### 3.3 MovimientoContable

Representa los movimientos contables (débitos y créditos).

**Relaciones principales:**
- `comprobante()`: Relación con el comprobante al que pertenece.
- `cuenta()`: Relación con la cuenta contable.
- `referencia()`: Relación polimórfica con la entidad que generó el movimiento.

## Flujos de Trabajo

### 1. Generación de Comprobantes

1. Se recibe una venta o compra.
2. Se validan los datos de IVA con `IvaValidationService`.
3. Se obtiene la plantilla de movimientos con `PlantillaComprobanteService`.
4. Se genera el comprobante con `ContabilidadService`.
5. Se registran los movimientos contables.

### 2. Generación de Reportes

1. Se reciben los parámetros del reporte (fechas, cuentas, etc.).
2. Se utilizan los métodos optimizados de `ContabilidadQueryService` para obtener los datos.
3. Se procesan y formatean los datos según el tipo de reporte.
4. Se genera la vista o exportación correspondiente.

## Optimizaciones

### 1. Caché

Se utiliza caché para:
- Saldos de cuentas a fechas específicas.
- Resultados de consultas frecuentes.
- Plantillas de comprobantes.

### 2. Consultas Optimizadas

- Uso de índices en tablas críticas.
- Consultas con joins eficientes.
- Selección específica de columnas necesarias.
- Agrupación y cálculos a nivel de base de datos.

### 3. Transacciones

Se utilizan transacciones para garantizar la integridad de los datos en operaciones que involucran múltiples tablas.

## Exportación de Datos

### 1. Exportación a Excel

Se utiliza la librería Maatwebsite/Laravel-Excel para exportar reportes a Excel.

**Clases principales:**
- `ReporteFiscalIvaExport`: Exporta el reporte fiscal de IVA a Excel.

## Pruebas Unitarias

Se han implementado pruebas unitarias para los servicios principales:

- `IvaValidationServiceTest`: Pruebas para validación de IVA.
- `PlantillaComprobanteServiceTest`: Pruebas para plantillas de comprobantes.
- `ContabilidadServiceTest`: Pruebas para generación de comprobantes.
- `ContabilidadQueryServiceTest`: Pruebas para consultas contables.

## Consideraciones de Seguridad

- Validación de datos de entrada en controladores.
- Uso de middleware de autenticación y autorización.
- Protección contra inyección SQL mediante el uso de Eloquent y Query Builder.
- Logging de operaciones críticas para auditoría.

## Mantenimiento y Escalabilidad

- Código modular y bien documentado.
- Separación clara de responsabilidades.
- Uso de interfaces y patrones de diseño.
- Optimizaciones de rendimiento para manejar grandes volúmenes de datos.

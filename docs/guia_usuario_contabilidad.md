# Guía de Usuario - Módulo Contable

## Introducción

Esta guía está diseñada para ayudar a los usuarios a utilizar eficientemente el módulo contable del sistema de ventas. Aquí encontrará instrucciones paso a paso para realizar las operaciones contables más comunes y generar los reportes necesarios para la gestión financiera y fiscal de su empresa.

## Acceso al Módulo Contable

1. Inicie sesión en el sistema con sus credenciales.
2. En el menú principal, haga clic en "Contabilidad".
3. Se mostrará el panel de control contable con acceso a todas las funcionalidades.

## Gestión de Cuentas Contables

### Consultar el Plan de Cuentas

1. En el menú de Contabilidad, seleccione "Plan de Cuentas".
2. Se mostrará la estructura jerárquica de cuentas contables.
3. Utilice el campo de búsqueda para encontrar cuentas específicas.
4. Haga clic en una cuenta para ver sus detalles y movimientos.

### Crear una Nueva Cuenta

1. En la vista de Plan de Cuentas, haga clic en "Nueva Cuenta".
2. Complete el formulario con la siguiente información:
   - Código de cuenta (respetando la estructura jerárquica)
   - Nombre de la cuenta
   - Tipo de cuenta (Activo, Pasivo, Patrimonio, Ingreso, Gasto)
   - Cuenta padre (si es una subcuenta)
   - Descripción (opcional)
3. Haga clic en "Guardar" para crear la cuenta.

## Comprobantes Contables

### Consultar Comprobantes

1. En el menú de Contabilidad, seleccione "Comprobantes".
2. Utilice los filtros para buscar comprobantes por:
   - Tipo (Ingreso, Egreso, Diario)
   - Fecha
   - Número de comprobante
   - Estado (Borrador, Aprobado, Anulado)
3. Haga clic en un comprobante para ver sus detalles y movimientos.

### Crear un Comprobante Manual

1. En la vista de Comprobantes, haga clic en "Nuevo Comprobante".
2. Seleccione el tipo de comprobante (Ingreso, Egreso, Diario).
3. Complete la información general:
   - Fecha
   - Descripción
   - Referencia (opcional)
4. Agregue los movimientos contables:
   - Seleccione la cuenta
   - Ingrese el valor en débito o crédito
   - Agregue una descripción para el movimiento
5. Verifique que el comprobante esté cuadrado (total débitos = total créditos).
6. Guarde el comprobante como borrador o apruébelo directamente.

### Aprobar un Comprobante

1. En la lista de comprobantes, busque el comprobante en estado "Borrador".
2. Haga clic en el botón "Aprobar".
3. Confirme la acción en el diálogo de confirmación.
4. El comprobante cambiará a estado "Aprobado" y no podrá ser modificado.

### Anular un Comprobante

1. En la lista de comprobantes, busque el comprobante que desea anular.
2. Haga clic en el botón "Anular".
3. Ingrese el motivo de la anulación.
4. Confirme la acción en el diálogo de confirmación.
5. El comprobante cambiará a estado "Anulado" y se registrará el motivo.

## Reportes Contables

### Balance General

1. En el menú de Contabilidad, seleccione "Reportes" y luego "Balance General".
2. Seleccione la fecha de corte para el balance.
3. Elija el nivel de detalle deseado (1, 2 o 3).
4. Haga clic en "Generar Reporte".
5. Se mostrará el balance general con activos, pasivos y patrimonio.
6. Utilice los botones de "Imprimir" o "Exportar" según necesite.

### Estado de Resultados

1. En el menú de Contabilidad, seleccione "Reportes" y luego "Estado de Resultados".
2. Seleccione el rango de fechas para el reporte.
3. Elija el nivel de detalle deseado (1, 2 o 3).
4. Haga clic en "Generar Reporte".
5. Se mostrará el estado de resultados con ingresos, gastos y utilidad/pérdida.
6. Utilice los botones de "Imprimir" o "Exportar" según necesite.

### Libro Diario

1. En el menú de Contabilidad, seleccione "Reportes" y luego "Libro Diario".
2. Seleccione el rango de fechas para el reporte.
3. Opcionalmente, marque "Incluir Comprobantes Anulados" si desea verlos.
4. Haga clic en "Generar Reporte".
5. Se mostrará el libro diario con todos los comprobantes y movimientos.
6. Utilice los botones de "Imprimir" o "Exportar" según necesite.

### Libro Mayor

1. En el menú de Contabilidad, seleccione "Reportes" y luego "Libro Mayor".
2. Seleccione la cuenta contable que desea consultar.
3. Seleccione el rango de fechas para el reporte.
4. Haga clic en "Generar Reporte".
5. Se mostrará el libro mayor con todos los movimientos de la cuenta seleccionada.
6. Utilice los botones de "Imprimir" o "Exportar" según necesite.

### Reporte Fiscal de IVA

1. En el menú de Contabilidad, seleccione "Reportes" y luego "Reporte Fiscal de IVA".
2. Seleccione el rango de fechas para el reporte (generalmente un mes o bimestre).
3. Haga clic en "Generar Reporte".
4. Se mostrará el reporte fiscal con:
   - Resumen de IVA generado (ventas)
   - Resumen de IVA descontable (compras)
   - Saldo a pagar o a favor
   - Detalle de ventas con IVA
   - Detalle de compras con IVA
5. Para exportar a Excel, haga clic en el botón "Exportar a Excel".
6. Para imprimir, haga clic en el botón "Imprimir".

## Cierre Contable

### Cierre Mensual

1. En el menú de Contabilidad, seleccione "Procesos" y luego "Cierre Mensual".
2. Seleccione el mes que desea cerrar.
3. El sistema verificará que no haya comprobantes pendientes en el período.
4. Haga clic en "Ejecutar Cierre".
5. El sistema generará automáticamente los comprobantes de cierre necesarios.
6. Se mostrará un mensaje de confirmación cuando el proceso finalice.

### Cierre Anual

1. En el menú de Contabilidad, seleccione "Procesos" y luego "Cierre Anual".
2. Seleccione el año que desea cerrar.
3. El sistema verificará que todos los meses del año estén cerrados.
4. Haga clic en "Ejecutar Cierre".
5. El sistema generará automáticamente los comprobantes de cierre anual.
6. Se mostrará un mensaje de confirmación cuando el proceso finalice.

## Integración con Ventas y Compras

### Comprobantes Automáticos de Ventas

Cuando se registra una venta en el sistema:
1. Se genera automáticamente un comprobante contable.
2. Se registran los movimientos correspondientes a:
   - Ingreso a caja o cuenta por cobrar
   - Ingreso por ventas
   - IVA generado (si aplica)
3. El comprobante queda en estado "Aprobado".
4. Puede consultarlo en la sección de Comprobantes filtrando por la referencia de la venta.

### Comprobantes Automáticos de Compras

Cuando se registra una compra en el sistema:
1. Se genera automáticamente un comprobante contable.
2. Se registran los movimientos correspondientes a:
   - Inventario o gasto
   - IVA descontable (si aplica)
   - Cuenta por pagar o salida de efectivo
3. El comprobante queda en estado "Aprobado".
4. Puede consultarlo en la sección de Comprobantes filtrando por la referencia de la compra.

## Solución de Problemas Comunes

### Comprobante Descuadrado

Si al crear un comprobante manual recibe el error "El comprobante no está cuadrado":
1. Verifique que la suma de los débitos sea igual a la suma de los créditos.
2. Revise si hay errores de digitación en los valores.
3. Asegúrese de que todos los movimientos necesarios estén incluidos.

### Saldos Incorrectos

Si detecta que los saldos en los reportes no coinciden con lo esperado:
1. Verifique que todos los comprobantes relevantes estén en estado "Aprobado".
2. Revise si hay comprobantes duplicados o anulados que puedan afectar el saldo.
3. Consulte el libro mayor de la cuenta para verificar todos los movimientos.

### Problemas con el IVA

Si los valores de IVA no coinciden con sus declaraciones fiscales:
1. Utilice el "Reporte Fiscal de IVA" para el período correspondiente.
2. Verifique que todas las ventas y compras tengan correctamente registrado el IVA.
3. Revise si hay facturas pendientes de registrar en el sistema.

## Contacto de Soporte

Si encuentra problemas que no puede resolver con esta guía, contacte al soporte técnico:
- Email: soporte@empresa.com
- Teléfono: (123) 456-7890
- Horario de atención: Lunes a Viernes, 8:00 AM - 6:00 PM

-- Script para añadir columnas de IVA a la tabla detalle_compras
ALTER TABLE detalle_compras ADD COLUMN tiene_iva BOOLEAN DEFAULT FALSE AFTER subtotal;
ALTER TABLE detalle_compras ADD COLUMN porcentaje_iva DECIMAL(5,2) DEFAULT 0 AFTER tiene_iva;
ALTER TABLE detalle_compras ADD COLUMN valor_iva DECIMAL(12,2) DEFAULT 0 AFTER porcentaje_iva;
ALTER TABLE detalle_compras ADD COLUMN total_con_iva DECIMAL(12,2) DEFAULT 0 AFTER valor_iva;

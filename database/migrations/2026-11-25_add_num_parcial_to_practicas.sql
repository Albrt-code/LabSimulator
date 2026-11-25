-- Ejecutar una sola vez sobre la base de datos lab_simulator.
-- Las prácticas existentes se asignan al primer parcial por defecto.
ALTER TABLE practicas
    ADD COLUMN num_parcial TINYINT NOT NULL DEFAULT 1 AFTER tipo_simulador;

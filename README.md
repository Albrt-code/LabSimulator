# LabSimulator

Plataforma web colaborativa de simulaciones virtuales y control de almacen para la academia de Fisica Experimental de la UPIICSA.
Desarrollado por Lopez Lozano Jorge Alberto y Luna Ramos Emmanuel Giovanni.

## Base de datos

Las migraciones SQL del proyecto se encuentran en `database/migrations/` y deben ejecutarse una sola vez sobre la base de datos `lab_simulator`.

Para habilitar la clasificación de las prácticas del catálogo por parcial, ejecutar:

```sql
database/migrations/2026-11-25_add_num_parcial_to_practicas.sql
```

La migración agrega `practicas.num_parcial` y asigna las prácticas existentes al primer parcial. En entornos donde esta columna ya exista, no se debe volver a ejecutar la migración.

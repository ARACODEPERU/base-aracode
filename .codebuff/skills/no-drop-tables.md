# Skill: Prohibido eliminar tablas o bases de datos

## Regla crítica — NUNCA romper

**Está absolutamente prohibido eliminar tablas o bases de datos en cualquier archivo de migración, seeder, comando de consola, o cualquier otro código del proyecto.**

## Instrucciones obligatorias

1. **JAMÁS usar `Schema::dropIfExists()`** ni `DB::statement('DROP TABLE ...')` ni `DROP DATABASE` en ningún archivo del proyecto, sin importar el contexto.

2. **Si una migración ya no es necesaria o quedó mal**: no se elimina su tabla. En su lugar se crea una migración nueva que deprecó o corrija lo necesario con `Schema::table()`, `rename()`, o eliminando solo columnas específicas si el usuario lo pide explícitamente.

3. **Si el usuario pide "quitar" una tabla o funcionalidad**: preguntar antes de actuar. Solo eliminar tabla si el usuario lo pide de forma **explícita y directa** (ej. "elimina la tabla X con drop"). Nunca asumir.

4. **En producción es aún más crítico**: las migraciones corren contra datos reales de clientes. Un `drop table` destruye información irrecuperable (ventas, pagos, personas, torneos).

5. **Al limpiar migraciones conflictivas o duplicadas** (como pasó con `sunat_currency_types` o parámetros `P000032`/`PHD0001`): corregir con `firstOrCreate`, `updateOrInsert`, o condicionales `Schema::hasTable()`/`hasColumn()` — nunca con drops.

## Excepción única

Solo cuando el usuario escriba explícitamente algo como "elimina la tabla tal con DROP TABLE" se podrá considerar, y aun así conviene confirmar con `ask_questions` antes de ejecutarlo.

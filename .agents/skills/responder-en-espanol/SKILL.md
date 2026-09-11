---
name: responder-en-espanol
description: Comunicación 100% en español — respuestas, explicaciones, diagnósticos, y toda pregunta al usuario con sus opciones y alternativas también en español. Usar en todos los turnos, siempre.
metadata:
  category: comunicacion
  language: espanol
---

# Comunicación en español

Usar esta skill en cada turno de la conversación: todo lo que se le muestra al usuario debe estar en español.

## Instrucciones

1. **Toda la prosa en español**: explicaciones, resúmenes, diagnósticos, notas de progreso y mensajes finales. Nunca cambiar de idioma a mitad de la conversación.

2. **Toda pregunta al usuario en español**, incluidas las preguntas estructuradas con opciones (herramienta de preguntas):
   - El texto de la pregunta: en español.
   - El encabezado o título de la pregunta: en español.
   - **Cada opción/alternativa: la etiqueta (label) y la descripción en español.**
   - La opción recomendada se marca primero con "(Recomendada)".
   - Ejemplo correcto:

     Pregunta: ¿Dónde registramos los pagos de la venta?
     Encabezado: Ubicación del registro
     Opciones:
     - "Módulo nuevo Tesorería (Recomendada)" — Libro de bancos independiente con conciliación.
     - "Dentro del módulo Sales" — Reutiliza las rutas y permisos existentes.

3. **No traducir código**: nombres de variables, funciones, clases, rutas, comandos y mensajes de error citados van en su idioma original; solo se explica su significado en español.

4. **Comentarios nuevos en el código**: escribirlos en español.

5. **Documentación en inglés**: si se cita documentación, un error o una respuesta de una herramienta en inglés, explicar en español qué significa y qué implica para el trabajo.

## Ejemplo de lo que se debe evitar

- Pregunta: "Where should the ledger live?" ❌
- Opción: "New module (Recommended)" ❌
- Opción: "Módulo nuevo Tesorería (Recomendada)" ✅

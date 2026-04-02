# Semana 2 - Solución del caso de estudio Román Bolaños

## Análisis del caso

En este caso, la institución maneja la información académica en hojas de cálculo. Aunque al principio eso puede parecer práctico, con el tiempo genera varios problemas, como duplicidad de datos, errores en las notas, dificultad para consultar información y riesgo de pérdida de archivos.

Desde mi punto de vista, el problema principal es que los archivos planos no están diseñados para controlar grandes volúmenes de información ni para que varias personas trabajen al mismo tiempo de forma segura. Por eso, un Sistema Gestor de Bases de Datos (SGBD) resulta más adecuado.

## Tarea A. Identificación de problemas

### 1. Listas duplicadas de estudiantes
El problema principal aquí es la **redundancia de datos**.  
Cuando la información se guarda en diferentes archivos o hojas, un mismo estudiante puede aparecer varias veces o con datos diferentes.

**¿Cómo ayuda un SGBD?**  
Permite crear una sola tabla de estudiantes con un identificador único, evitando duplicados y manteniendo la información organizada.

### 2. Notas inconsistentes entre docente y coordinación
Aquí el problema es la **falta de integridad de los datos**.  
Al manejar notas en varios archivos, se pueden presentar diferencias entre una versión y otra.

**¿Cómo ayuda un SGBD?**  
Un SGBD permite establecer reglas y relaciones para asegurar que cada nota quede correctamente asociada al estudiante y la asignatura correspondiente.

### 3. Dificultad para consultar el historial académico
El problema en este punto es la **limitación para hacer consultas**.  
Buscar el historial completo de un estudiante en varios archivos toma tiempo y puede generar errores.

**¿Cómo ayuda un SGBD?**  
Con consultas SQL se puede obtener rápidamente toda la información de un estudiante, como materias vistas, notas y periodos cursados.

### 4. Riesgo de pérdida de información
Aquí el problema está relacionado con la **seguridad y administración de los datos**.  
Si se sobrescribe un archivo o se elimina por error, se puede perder información importante.

**¿Cómo ayuda un SGBD?**  
Ofrece copias de seguridad, recuperación de datos y control de acceso, reduciendo el riesgo de pérdida de información.

## Tarea B. Discusión guiada

### ¿Cuándo se debe migrar de archivos planos a un SGBD?
Se debe migrar cuando la información empieza a crecer, cuando varias personas necesitan acceder a los datos, cuando aparecen errores frecuentes o cuando ya no es fácil mantener el control manualmente.

En mi opinión, algunas señales claras para hacer esa migración son:

- existencia de datos duplicados,
- inconsistencias en la información,
- dificultad para consultar registros,
- necesidad de mayor seguridad,
- y dependencia cada vez mayor de los datos para tomar decisiones.

### ¿Qué tipo de SGBD es más adecuado para este caso?
Considero que el más adecuado es un **SGBD relacional**.

Esto se debe a que en un sistema académico la información tiene una estructura bien definida: estudiantes, asignaturas, docentes, matrículas y notas. Todas estas entidades se pueden organizar en tablas y relacionarse entre sí de manera clara.

Además, una base de datos relacional ofrece consistencia, orden y precisión, lo cual es fundamental en un entorno académico.


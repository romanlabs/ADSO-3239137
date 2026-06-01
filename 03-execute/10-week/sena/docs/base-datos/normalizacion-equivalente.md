# Normalizacion Equivalente

Aunque MongoDB no usa normalizacion relacional estricta, se aplico una separacion equivalente:

- Primera forma normal: cada campo almacena valores atomicos.
- Segunda forma normal: los datos del aprendiz dependen del aprendiz, no de la matricula.
- Tercera forma normal: la informacion personal no se repite en `matriculas`; se referencia mediante `aprendizId`.

Esta decision evita inconsistencias. Si cambia el telefono o programa de un aprendiz, se actualiza una sola vez en `aprendices`.

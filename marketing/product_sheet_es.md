# Ficha de producto del módulo Next Order Discount para PrestaShop

Textos en español listos para usar en la ficha de producto, la web del módulo y los materiales del equipo comercial. Las formulaciones describen las funcionalidades reales de la versión 1.0.0, sin prometer un crecimiento garantizado de las ventas.

---

## 1. Nombre del módulo

### Variante recomendada

**Next Order Discount: cupón personal para el próximo pedido**

El nombre explica de inmediato el funcionamiento del módulo, subraya el carácter personal de la oferta e incluye la búsqueda principal «cupón para el próximo pedido».

### Variante centrada en el objetivo de negocio

**Cupón personal para el próximo pedido: recupere a sus clientes**

### Variante centrada en la automatización

**Cupón personal automático después de la compra**

---

## 2. Descripción corta

**Cree un motivo para volver a comprar en cuanto el pedido se completa: emita cupones personales de forma automática, envíe correos y recordatorios y siga el resultado en un único embudo.**

---

## 3. Descripción completa

# Recupere a su cliente con un cupón personal

Cuando el pedido se haya completado, envíe a su cliente un cupón personal para su próxima compra: descuento porcentual, importe fijo o envío gratuito.

**Next Order Discount** crea un cupón personal en cuanto un pedido cumple las condiciones definidas y envía al cliente un correo con el código y sus condiciones de uso. Si el cupón queda sin utilizar, el módulo puede enviar hasta dos recordatorios automáticos. Las reglas de emisión, los correos y las estadísticas están disponibles en el back office de PrestaShop.

## Cómo funciona

1. Usted crea una regla y elige qué pedidos participan en la campaña.
2. Cuando un pedido cumple todas las condiciones y alcanza el estado requerido, el módulo crea un cupón personal y envía de inmediato el correo al cliente.
3. El módulo envía automáticamente los recordatorios programados, reintenta los envíos fallidos y actualiza los estados de los cupones.
4. En el panel de control ve el recorrido de los cupones, desde su creación hasta su uso.

Tras la configuración inicial, la campaña funciona sola y los resultados siguen bajo su control.

## Cree ofertas distintas para objetivos distintos

En lugar de un único descuento para todos, cree ofertas independientes para clientes nuevos, clientes habituales, pedidos grandes, determinados países o categorías de productos concretas. Cada regla define quién recibe un cupón, qué ventaja obtiene y durante cuánto tiempo puede aprovecharla.

Cada regla ofrece tres tipos de ventaja:

- descuento porcentual;
- importe de descuento fijo;
- envío gratuito.

El periodo de validez del cupón y el importe mínimo del próximo pedido se configuran por separado.

Ejemplos de escenarios:

- **10 % tras el primer pedido**: animar con suavidad a un cliente nuevo a realizar su segunda compra;
- **15 € de descuento tras un pedido de 120 € o más**: recompensar a los clientes con un ticket alto;
- **envío gratuito para clientes habituales**: ofrecer un privilegio a un grupo concreto;
- **cupón de temporada para marcas o categorías concretas**: apoyar una campaña dirigida sin rebajar todo el catálogo.

## Emita el cupón solo a los clientes adecuados

Todas las condiciones de una regla se comprueban al mismo tiempo. Puede configurar:

- los estados de pedido que generan el cupón;
- el importe mínimo y máximo del pedido de origen;
- el periodo de actividad de la campaña;
- el número de pedido del cliente: por ejemplo, solo el primer pedido o a partir del tercero;
- los grupos de clientes;
- los países;
- las monedas;
- las categorías de productos;
- las marcas.

Para las condiciones por lista están disponibles los modos «Todos», «Solo los seleccionados» y «Todos excepto los seleccionados».

Las reglas se evalúan por prioridad. La opción de detener la evaluación le permite decidir si el cliente recibe un solo cupón o varios cupones procedentes de distintas reglas coincidentes.

## Adapte cada correo a la campaña

Cada regla dispone de su propio conjunto de correos:

- el correo principal con el cupón;
- el primer recordatorio;
- el segundo recordatorio.

El asunto y el contenido HTML se configuran por separado para cada idioma de la tienda. Los marcadores insertan el código y el importe del descuento, la fecha de caducidad, el importe mínimo de pedido, el nombre del cliente, el nombre de la tienda y otros datos.

Antes de lanzar la campaña, abra la vista previa del correo y envíese una copia de prueba.

## Vuelva a llamar la atención sobre un cupón sin usar

Configure uno o dos recordatorios y elija cómo se calcula la fecha:

- un número determinado de días después del envío del correo principal;
- un número determinado de días antes de que caduque el cupón.

Si el cupón ya se ha utilizado, ha caducado o se ha cancelado, no se envían más recordatorios.

## Controle el formato del código promocional

Para cada regla puede configurar:

- la longitud de la parte aleatoria del código;
- el conjunto de caracteres: letras, cifras o una combinación de ambos;
- una plantilla con la variable `%key%`.

Por ejemplo, la plantilla `RETURN-%key%` puede generar el código `RETURN-AB12CD8X`.

## Controle el resultado en un único embudo

El panel de control muestra el número de cupones en cada etapa:

**creados → enviados → con recordatorio → usados → caducados → cancelados**.

Ahí mismo se calcula la conversión de cupones creados a cupones usados y se muestra el estado de la cola de reenvíos y recordatorios. Esto ayuda a evaluar el uso real de la oferta y a detectar a tiempo un problema de envío.

## Automatice las tareas en segundo plano

El planificador cron se encarga de programar y enviar los recordatorios, de reintentar el envío tras un fallo del correo principal y de pasar los cupones vencidos al estado correspondiente.

En la pestaña de herramientas puede:

- instalar la tarea cron automáticamente, si el servidor lo permite;
- copiar un comando listo para el planificador del sistema o para uno externo;
- ejecutar todas las tareas manualmente para comprobarlas;
- consultar la hora de la última ejecución y el estado de cada tarea;
- revisar la cola de reenvíos y recordatorios.

## Proteja la campaña de errores y descuentos innecesarios

- Para un mismo pedido de origen y una misma regla, el cupón se crea una sola vez.
- Si el pedido de origen se cancela o se reembolsa, el cupón asociado puede desactivarse automáticamente.
- Se comprueba que los códigos aleatorios sean únicos.
- El registro de eventos ayuda a localizar errores y a supervisar las operaciones en segundo plano.
- El periodo de conservación del registro es configurable.

## Qué gana la tienda

- un escenario de recuperación de clientes listo para usar en cuanto el pedido se completa;
- ofertas personales en lugar de un único descuento para todos;
- cupones personales basados en las reglas de carrito estándar de PrestaShop;
- correos configurables y hasta dos recordatorios;
- control del periodo de validez y cancelación automática de los cupones que dejan de ser válidos;
- un embudo de uso de cupones y un registro de actividad del módulo;
- compatibilidad con multitienda y correos en los idiomas de la tienda.

---

## 4. Funciones principales

### Reglas y descuentos

- número ilimitado de reglas;
- prioridad de las reglas y detención de la evaluación;
- descuento porcentual, importe fijo o envío gratuito;
- periodo de validez e importe mínimo del próximo pedido para cada regla;
- cupón personal vinculado al cliente;
- protección frente a una doble emisión para un mismo pedido y una misma regla;
- formato del código de cupón configurable.

### Condiciones de emisión

- estados de pedido seleccionados;
- rango de importe del pedido de origen;
- periodo de actividad de la regla;
- número de pedido del cliente;
- grupos de clientes, países y monedas;
- categorías de productos y marcas;
- modos de inclusión y exclusión para las condiciones por lista.

### Correos y recordatorios

- correo principal propio para cada regla;
- hasta dos correos de recordatorio;
- textos de los correos para cada idioma de la tienda;
- marcadores dinámicos;
- vista previa del correo;
- envío de prueba;
- detención automática de los recordatorios tras el uso, la cancelación o la caducidad del cupón.

### Control y automatización

- embudo de estados de los cupones y conversión de uso;
- lista de cupones emitidos con filtros;
- reenvío manual del correo y de los recordatorios;
- cola de reenvíos y recordatorios;
- asistente de configuración del cron;
- ejecución manual de las tareas y control de su estado;
- caducidad automática de los cupones;
- cancelación automática del cupón según los estados del pedido de origen;
- registro de eventos con filtros y periodo de conservación configurable;
- modo de depuración;
- compatibilidad con multitienda.

---

## 5. Preguntas y respuestas

### ¿En qué se diferencia este módulo de un código promocional corriente?

Un código promocional corriente suele anunciarse a todos los clientes antes de la compra en curso o durante ella. Next Order Discount crea un cupón personal para un cliente concreto después de un pedido válido y lo motiva a volver para su siguiente compra.

### ¿Cuándo se crea el cupón?

Cuando el pedido alcanza uno de los estados seleccionados en la regla y, al mismo tiempo, cumple todas las demás condiciones de esa regla.

### ¿Se puede emitir el cupón solo después del primer pedido?

Sí. Indique 1 como número de pedido mínimo y máximo. Del mismo modo puede configurar una campaña para el segundo pedido, el tercero o los siguientes.

### ¿Se pueden ofrecer descuentos distintos a clientes distintos?

Sí. Cree varias reglas con condiciones, descuentos y prioridades diferentes. Puede tener en cuenta el grupo del cliente, el país, la moneda, el importe del pedido, los productos de determinadas categorías o marcas y otros parámetros.

### ¿Se pueden emitir varios cupones por un mismo pedido?

Sí, si el pedido coincide con varias reglas y en esas reglas no está activada la detención de la evaluación. Si solo desea un cupón, establezca las prioridades y active la detención en la regla que corresponda.

### ¿Qué tipos de descuento admite?

Descuento porcentual, importe fijo y envío gratuito.

### ¿Se puede exigir un importe mínimo en el próximo pedido?

Sí. El importe mínimo de uso se define por separado para cada regla.

### ¿El módulo envía por sí mismo el correo con el cupón?

Sí. Tras el cambio de estado de un pedido válido, el módulo crea el cupón y envía de inmediato el correo a través del sistema de correo de PrestaShop. Si el envío falla, el correo pasa a la cola para un nuevo intento mediante el cron.

### ¿Cuántos recordatorios se pueden enviar?

Hasta dos. Cada recordatorio puede desactivarse, y su fecha de envío puede calcularse desde el correo principal o desde la fecha de caducidad del cupón.

### ¿Se enviará un recordatorio después de usar el cupón?

No. No se programan recordatorios para los cupones usados, caducados o cancelados.

### ¿Qué ocurre si el pedido de origen se cancela o se reembolsa?

Si el nuevo estado del pedido figura en la lista de estados de cancelación, el módulo desactiva el cupón asociado y lo marca como cancelado.

### ¿Se puede modificar el texto del correo?

Sí. Para cada regla y cada idioma de la tienda puede modificar el asunto y el contenido HTML del correo principal y de los dos recordatorios. Dispone de vista previa y de envío de prueba.

### ¿El cliente verá un bloque nuevo en la tienda?

No. El módulo actúa después del pedido e informa del cupón por correo electrónico, así que no requiere integrar ningún widget en la plantilla de la tienda.

### ¿Cómo se evalúa la eficacia de la campaña?

El panel de control muestra cuántos cupones se han creado, enviado, usado, caducado y cancelado, así como la conversión de cupones creados a cupones usados.

### ¿Es compatible con multitienda?

Sí. Los datos y los ajustes tienen en cuenta la tienda seleccionada, y en el modo «todas las tiendas» hay estadísticas consolidadas.

### ¿Qué versiones son compatibles?

El módulo está diseñado para PrestaShop 8.1 y versiones posteriores. La versión mínima de PHP es la 7.2; además, el servidor debe cumplir los requisitos del sistema de la versión de PrestaShop instalada.

---

## 6. Palabras clave de búsqueda

cupón próximo pedido, descuento próximo pedido, código promocional después de la compra, cupón automático, cupón personalizado, recompra, ventas recurrentes, recuperación de clientes, fidelización de clientes, descuento después del pedido, correo con cupón, recordatorio de cupón, automatización de descuentos, reglas de descuento PrestaShop, cupón PrestaShop, programa de fidelización PrestaShop, envío gratis próximo pedido, conversión de cupones

---

## 7. Texto para la sección «Novedades»

**Versión 1.0.0: primera publicación**

- Creación automática de un cupón personal en cuanto el pedido pasa al estado elegido.
- Número ilimitado de reglas con prioridad y condiciones flexibles.
- Descuento porcentual, importe fijo o envío gratuito.
- Segmentación por importe y número de pedido, periodo de campaña, grupos, países, monedas, categorías y marcas.
- Correos propios para cada regla y cada idioma de la tienda.
- Hasta dos recordatorios automáticos.
- Formato del código de cupón configurable.
- Cancelación automática y gestión de la caducidad.
- Embudo de cupones, cola de reenvíos y recordatorios, herramientas de cron y registro de eventos.
- Compatibilidad con multitienda.

---

## 8. Acentos recomendados para la ficha de producto

La primera pantalla de la ficha debe responder a tres preguntas:

1. **¿Qué hace el módulo?** Crea un cupón personal después del pedido.
2. **¿Para qué sirve?** Da al cliente un motivo concreto para volver.
3. **¿Por qué es cómoda la solución?** Reglas, correos, recordatorios y control reunidos en un solo módulo.

Titular recomendado para la primera pantalla:

> **Convierta un pedido completado en un motivo para la siguiente compra**

Subtítulo recomendado:

> **Cree cupones personales después de la compra, recuerde la ventaja a sus clientes y siga el resultado en un único embudo.**

Para las capturas de pantalla del listado conviene mostrar:

1. el panel de control con el embudo de cupones;
2. la lista de reglas y sus prioridades;
3. las condiciones de segmentación;
4. la configuración del descuento y del periodo de validez;
5. el editor y la vista previa del correo;
6. la lista de cupones emitidos;
7. el estado del cron y de la cola de reenvíos y recordatorios.

En cada imagen utilice una sola idea breve en lugar de enumerar todas las funciones. El hilo conductor de toda la ficha: **«Un motivo personal para volver, automáticamente después de cada pedido válido»**.

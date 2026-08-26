# Control de Facturas - MDP Soluciones

Sistema simple para registrar compras y saber si ya tenés (o falta) la factura A de cada una.

## Qué guarda por cada compra
- ID (automático)
- Fecha
- Sitio donde se compró
- Vendedor
- Producto/s
- Monto
- Si tenés la factura A (sí/no)
- Link al PDF (por ejemplo, un archivo en Google Drive)
- Notas
- Quién la registró y quién la modificó

## Instalación en Hostinger (mismo patrón que el sistema de presupuestos)

1. Subí toda la carpeta `facturas-tracker` por FileZilla a tu hosting (por ejemplo a un subdominio como `facturas.mdpsoluciones.com.ar` o a una subcarpeta).
2. Entrá a `config.php` y cambiá el valor de `SETUP_KEY` por una clave propia (es la que te permite crear el primer usuario).
3. Dale permisos de escritura a la carpeta `data/` (chmod 755 o 775 según lo permita Hostinger) para que pueda guardar los JSON.
4. Andá a `https://tu-dominio/gestionar_usuarios.php?key=TU_SETUP_KEY` y creá tu primer usuario (y los que necesites, uno por persona).
5. **Importante:** una vez creados los usuarios que necesitás, borrá `gestionar_usuarios.php` del servidor o cambiá la `SETUP_KEY`, para que nadie más pueda crear usuarios.
6. Entrá a `login.php` con el usuario y clave creados.

## Uso de Google Drive para los PDF

No hay subida automática de archivos (eso requeriría conectar la cuenta de Google con OAuth, que es mucho más complejo de mantener). El flujo simple:

1. Subís el PDF de la factura a una carpeta de Drive.
2. Click derecho → "Obtener enlace" → asegurate que el enlace esté como "Cualquier persona con el enlace puede ver".
3. Pegás ese link en el campo "Link al PDF" al cargar o editar la compra.

Si en el futuro querés que la subida sea automática desde el formulario, se puede armar con la API de Google Drive, pero es un desarrollo aparte.

## Cómo se usa el sistema
- **+ Nueva compra**: carga un registro nuevo.
- Click en el **badge Sí/No** de la columna "Factura A": cambia el estado rápido sin abrir el formulario.
- **Buscar**: filtra por sitio, vendedor o producto.
- **Filtro Con/Sin factura**: para ver rápido qué falta reclamar.
- El resumen arriba de la tabla muestra cuántas compras totales y cuántas están sin factura.

## Estructura de archivos
```
facturas-tracker/
  config.php              -> configuración y helpers
  login.php               -> ingreso
  logout.php              -> cerrar sesión
  gestionar_usuarios.php  -> alta de usuarios (borrar o proteger después de usar)
  index.php               -> pantalla principal
  api.php                 -> backend (guarda/lee/edita/borra compras)
  assets/style.css        -> estilos con la identidad MDP (colores, tipografías)
  assets/app.js           -> lógica del frontend
  data/users.json         -> usuarios (claves hasheadas, nunca en texto plano)
  data/compras.json       -> todas las compras registradas
  data/.htaccess          -> bloquea el acceso directo a los JSON desde el navegador
```

## Notas técnicas
- Login multiusuario con clave hasheada (`password_hash`/`password_verify` de PHP), igual de seguro que cualquier sistema estándar.
- Todos los usuarios ven las mismas compras (no hay compras "privadas" por usuario), pero cada registro guarda quién la cargó y quién la modificó por última vez.
- Guardado igual que el sistema de presupuestos: archivos JSON planos en el servidor, sin base de datos.
- Recordá el hard refresh (Cmd+Shift+R) después de subir cambios de CSS/JS por el caché agresivo de Hostinger.

INSTRUCCIONES - App PHP para stock de celulares

Archivos incluidos:
- u745852668_stock_cel.sql  -> script SQL para crear la base y tablas + usuario admin (admin/admin123)
- config.php -> configuración de conexión (editar con datos de Hostinger)
- login.php, logout.php -> manejo de sesión
- index.php -> listado y búsqueda de stock
- add_model.php -> agregar modelos
- add_variant.php -> agregar variantes y stock inicial
- edit_stock.php -> editar cantidad de stock

Pasos para instalar en Hostinger:
1) Subir los archivos al public_html (o carpeta deseada).
2) Editar config.php con los datos reales (DB host, user, pass).
3) Importar el archivo u745852668_stock_cel.sql en hPanel -> Bases de datos -> phpMyAdmin (o ejecutar desde Workbench).
4) Acceder a /login.php y usar usuario: admin / contraseña: admin123 (recomendado cambiar la contraseña luego).

Notas de seguridad:
- Cambiar la contraseña del admin tras el primer acceso.
- En producción, usar HTTPS y restringir accesos al panel si es necesario.
- Este es un ejemplo básico pensado para arrancar rápido.

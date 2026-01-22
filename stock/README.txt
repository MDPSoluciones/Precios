README - Phone stock PHP app (English)

Files included:
- u745852668_stock_cel.sql  -> SQL script to create schema (no admin inserted)
- create_admin.php -> run once to create admin user with default password admin123, then delete this file
- config.php -> DB connection (edit with your Hostinger values)
- styles.css -> styles with your color palette
- login.php, logout.php -> session management
- index.php -> list, search and links to edit brand/model/variant/stock
- add_brand.php, add_model.php, add_variant.php -> create records
- edit_brand.php, edit_model.php, edit_variant.php, edit_stock.php -> edit records
- change_password.php -> change logged user password
- README.txt -> this file

Installation steps:
1) Upload files to public_html (or a subfolder) on Hostinger.
2) Edit config.php with your DB host, user and password.
3) Import u745852668_stock_cel.sql in hPanel -> phpMyAdmin (or run it from Workbench).
4) Upload create_admin.php and open it in the browser to create the admin user (admin/admin123). Then DELETE create_admin.php.
5) Open /login.php and log in with: admin / admin123. Change the password immediately.

Security notes:
- Use HTTPS in production.
- Change admin password after first login.
- Delete create_admin.php after use.

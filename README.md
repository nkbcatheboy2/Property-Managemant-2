# LDA Property Management Portal

## Setup
1. Copy this folder to `C:\xampp\htdocs\property-management` (or use the current folder URL if renamed).
2. Start Apache and MySQL in XAMPP.
3. Import `database.sql` in phpMyAdmin for database `property_management`.
4. Open `/create_admin.php` once, then delete that file.
5. Sign in at `/login.php` with `admin` / `admin123`.

The demo citizen OTP is `111000`; it is deliberately local and does not send SMS. Configure MySQL credentials in `config/db.php` if needed. Upload directories need write permission. `update_v2.sql` and `lda_portal_schema.sql` are compatibility scripts for existing installations.

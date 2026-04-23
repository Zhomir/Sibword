# MySQL Setup Guide (Local/Server)

## 1) Create database from DDL
Use:
- `database/sql/mysql_schema_v1.sql`

Example:
```bash
mysql -u root -p < database/sql/mysql_schema_v1.sql
```

## 2) Configure Laravel `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mvpsibword
DB_USERNAME=root
DB_PASSWORD=your_password
```

## 3) Clear config cache
```bash
php artisan config:clear
php artisan cache:clear
```

## 4) Data migration strategy
- For production move: use one-time transfer scripts from SQLite to MySQL.
- For clean deployment: run MySQL DDL first, then import seed/reference data.

## Notes
- Existing Laravel migrations are still SQLite-oriented and not yet aligned with full target schema.
- Target schema for MVP + additional features is maintained in SQL-first mode at this stage.

# Debug Log

## Problem: Server returned 500 / 502

---

## Findings

### 1. Missing `APP_KEY`
- `.env` not copied into Docker image (standard `.dockerignore`)
- `docker-compose.prod.yml` had no `APP_KEY` env var
- Laravel requires `APP_KEY` for encryption (sessions, cookies, etc.)

### 2. MySQL ransomwared
- Port 3306 exposed on `0.0.0.0` with password `password`
- Attacker dropped all databases, created `RECOVER_YOUR_DATA` ransom note
- Root privileges stripped (`CREATE`, `INSERT`, `DROP`, `DELETE`, `UPDATE` revoked)

### 3. Missing `redis` PHP extension
- `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`
- `phpredis` extension not installed in Docker image, no `predis` composer package either
- Error: `Class "Redis" not found`

### 4. No error output
- `APP_DEBUG=false` (production config cached), errors silently logged to `storage/logs/laravel.log`

---

## Fixes

### APP_KEY
```
Generated: base64:rg4WL3FyZYax+dwy5roNa/Q5r+NiXApNC+84VKyr2FY=
Added to docker-compose.prod.yml → app.environment.APP_KEY
```

### MySQL security
```
Changed root password: password → new_secure_pass_123
Bound port to 127.0.0.1 (was 0.0.0.0)
Removed RECOVER_YOUR_DATA database
Recreated laravel_perf database
Restored GRANT ALL PRIVILEGES ON *.* TO 'root'@'%'
```

### Redis dependency removal
```
CACHE_STORE: redis → file
SESSION_DRIVER: redis → file
QUEUE_CONNECTION: redis → sync
Removed redis from app.depends_on
```
(To re-enable Redis, install `phpredis` extension in Dockerfile via `pecl install redis`)

---

## Commands used

```bash
# Check app container env
docker exec laravel_app_prod sh -c 'echo APP_KEY=$APP_KEY'

# Generate APP_KEY
docker exec laravel_app_prod php -r "echo 'base64:' . base64_encode(random_bytes(32));"

# Test PDO connection
docker exec laravel_app_prod php -r 'try { $pdo = new PDO("mysql:host=db;port=3306;dbname=laravel_perf", "root", "password"); echo "OK"; } catch (PDOException $e) { echo $e->getMessage(); }'

# Check databases
docker exec laravel_db_prod mysql -uroot -ppassword -e 'SHOW DATABASES;'

# Fix MySQL privileges
docker exec laravel_db_prod mysql -uroot -ppassword -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;"

# Change root password
docker exec laravel_db_prod mysql -uroot -ppassword -e "ALTER USER 'root'@'%' IDENTIFIED BY 'new_secure_pass_123'; ALTER USER 'root'@'localhost' IDENTIFIED BY 'new_secure_pass_123';"

# Check Laravel error log
cat /var/www/storage/logs/laravel.log
```

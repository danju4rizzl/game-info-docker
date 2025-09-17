# WordPress Docker Upgrade Guide (Windows + Docker Desktop)

This project runs WordPress in Docker with MariaDB.

Current compose defaults:
- DB: mariadb:10.11 (volume: db_data)
- WP: wordpress:6.8.2-php8.3-apache (ports: 8000)
- wp-content mounted from ./wp-content

## 0) Pre‑checks
- Ensure Docker Desktop is running.
- Verify you can access: http://localhost:8000/wp-admin
- Note your admin credentials.

## 1) Back up before upgrading (required)
Create both a DB dump and a wp-content archive.

PowerShell (run from repo root):

```
# DB dump
mkdir -Force .\.backups | Out-Null
$ts = Get-Date -Format "yyyyMMdd-HHmmss"
docker compose exec db bash -lc "mysqldump -uroot -p$env:DB_ROOT_PASSWORD --databases ${env:DB_NAME:-wordpress} > /tmp/wp-$ts.sql"
docker compose cp db:/tmp/wp-$ts.sql ./.backups/wp-$ts.sql

# wp-content archive
Compress-Archive -Path .\wp-content\* -DestinationPath .\.backups\wp-content-$ts.zip
```

If DB_ROOT_PASSWORD/DB_NAME are not set in your environment, defaults are used (rootpass / wordpress). You can also dump with the app user:

```
docker compose exec db bash -lc "mysqldump -u${env:DB_USER:-wordpress} -p${env:DB_PASSWORD:-wordpress} ${env:DB_NAME:-wordpress} > /tmp/wp-$ts.sql"
```

## 2) Update images
We pin WordPress to a specific version tag to avoid surprise upgrades.

Commands:
```
docker compose pull
docker compose up -d --remove-orphans
```

## 3) Run WordPress DB upgrade
Visit: http://localhost:8000/wp-admin/upgrade.php and follow prompts. WordPress may briefly enter maintenance mode.

## 4) Update plugins and themes
- In wp-admin, go to Dashboard > Updates.
- Update plugins and themes. Prefer updating one by one and test.
- If a plugin breaks on PHP 8.3, temporarily disable via CLI:
  ```
  docker compose exec wordpress wp plugin deactivate <slug>
  ```
  If wp-cli is missing, you can rename the plugin folder under ./wp-content/plugins.

## 5) Verify
- Frontend loads without errors
- Admin dashboard shows WordPress up-to-date
- Check Site Health (Tools > Site Health)

## 6) Rollback (if needed)
- Stop containers: `docker compose down`
- Restore DB: `docker compose up -d db` then
  `docker compose cp ./.backups/wp-<timestamp>.sql db:/tmp/restore.sql`
  `docker compose exec db bash -lc "mysql -uroot -p$env:DB_ROOT_PASSWORD < /tmp/restore.sql"`
- Restore wp-content: replace ./wp-content with the contents of your backup zip.
- Optionally pin WP to an earlier tag (e.g., wordpress:6.6.2-php8.2-apache) in docker-compose.yml and run `docker compose up -d`.

## Notes
- We bind‑mount only wp-content so WordPress core files come from the image (safer upgrades).
- MariaDB 10.11 is LTS and compatible with WP 6.8.x.
- If you need phpMyAdmin: http://localhost:8080 (user/pass from env or defaults in compose).


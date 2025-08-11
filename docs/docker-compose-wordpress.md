# Wordpress Docker Setup

This project includes a Docker Compose setup for a local WordPress development environment optimized for Windows 11.

## Services

- db: MariaDB 10.11 with persistent volume
- wordpress: WordPress 6.6 (PHP 8.3, Apache)
- phpmyadmin: Optional database UI on port 8080

## Persistent Data

- Database data persists in Docker named volume `db_data`.
- WordPress `wp-content` is bind-mounted from `./wp-content` to enable theme/plugin development.

## Prerequisites

- Docker Desktop for Windows (WSL2 backend recommended)
- Optional: Create an `.env` file to override defaults.

Example `.env`:

```
DB_ROOT_PASSWORD=changeme
DB_NAME=wordpress
DB_USER=wordpress
DB_PASSWORD=wordpress
```

## Usage

1. Create the `wp-content` folder if it doesn't exist:

   ```powershell
   mkdir wp-content
   ```

2. Start the stack:

   ```powershell
   docker compose up -d
   ```

3. Access services:

   - WordPress: http://localhost:8000
   - phpMyAdmin: http://localhost:8080 (host: `db`, user: `DB_USER`, pass: `DB_PASSWORD`)

4. Stop the stack:

   ```powershell
   docker compose down
   ```

5. Reset database (dangerous – removes DB data only):
   ```powershell
   docker volume rm game-info_db_data
   ```

## Notes for Windows 11

- Use the WSL2 backend in Docker Desktop for best file I/O performance.
- Bind mounts use relative paths (e.g., `./wp-content`) and are compatible with Windows paths.
- The `FS_METHOD=direct` is set via `WORDPRESS_CONFIG_EXTRA` so plugin/theme updates work with bind mounts.

## Customization

- To add additional PHP extensions or configuration, consider building a custom image FROM `wordpress:6.6-php8.3-apache`.
- To map uploads or other directories, extend the volumes section of the `wordpress` service.

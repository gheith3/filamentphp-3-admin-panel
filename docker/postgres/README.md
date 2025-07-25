# PostgreSQL Support for Laravel Filament Admin Panel

This directory contains PostgreSQL configuration and initialization scripts for the Laravel Filament Admin Panel.

## Configuration

The PostgreSQL service is now included in the `docker-compose.yml` file alongside the existing MariaDB service.

### Environment Variables

To use PostgreSQL, update your `.env` file with these settings:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=admin_panel
DB_USERNAME=admin_panel_user
DB_PASSWORD=password
```

### Alternative: Use MariaDB

If you prefer to use MariaDB instead, use these settings:

```env
DB_CONNECTION=mariadb
DB_HOST=database
DB_PORT=3306
DB_DATABASE=admin_panel
DB_USERNAME=admin_panel_user
DB_PASSWORD=password
```

## Services

### PostgreSQL Service

-   **Image**: postgres:16-alpine
-   **Port**: 5432 (exposed on host)
-   **Database**: admin_panel
-   **User**: admin_panel_user
-   **Password**: password

### MariaDB Service (Legacy)

-   **Image**: mariadb:10.11
-   **Port**: 3307 (exposed on host)
-   **Database**: admin_panel
-   **User**: admin_panel_user
-   **Password**: password

## Initialization

The `init/01-init.sql` script runs automatically when PostgreSQL starts for the first time. It:

1. Grants all privileges to the admin_panel_user
2. Sets default privileges for future objects
3. Installs common PostgreSQL extensions:
    - `uuid-ossp` - UUID generation functions
    - `pgcrypto` - Cryptographic functions
4. Creates a healthcheck function

## Usage

1. Build and start the services:

    ```bash
    docker-compose up --build
    ```

2. The application will be available at `http://localhost:8100`

3. PostgreSQL will be accessible at `localhost:5432`

4. MariaDB will be accessible at `localhost:3307` (if needed)

## Database Management

### Connecting to PostgreSQL

```bash
# From host
docker exec -it <container_name> psql -U admin_panel_user -d admin_panel

# Or using a PostgreSQL client
psql -h localhost -p 5432 -U admin_panel_user -d admin_panel
```

### Connecting to MariaDB

```bash
# From host
docker exec -it <container_name> mysql -u admin_panel_user -p admin_panel

# Or using a MySQL client
mysql -h localhost -P 3307 -u admin_panel_user -p admin_panel
```

## Health Checks

Both database services include health checks:

-   **PostgreSQL**: `pg_isready` command
-   **MariaDB**: Built-in healthcheck script

You can check the status with:

```bash
docker-compose ps
```

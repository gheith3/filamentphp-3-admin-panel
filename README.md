# Laravel 12 + FilamentPHP 3 Admin Panel

A comprehensive admin panel built with Laravel 12 and FilamentPHP 3, featuring user management, role-based permissions, system monitoring, backup management, and Docker deployment.

## 🚀 Features

### Core Features

-   **Laravel 12** - Latest Laravel framework with modern PHP 8.2+ features
-   **FilamentPHP 3** - Modern admin panel interface with rich components
-   **User Management** - Complete CRUD operations for users
-   **Role & Permission System** - Advanced role-based access control using Spatie Laravel Permission
-   **User Profile Management** - Self-service profile editing with avatar support
-   **Authentication** - Secure login system with session management

### System Management

-   **System Health Monitoring** - Real-time system metrics and health checks
-   **Backup Management** - Automated backup system for database and files
-   **Performance Monitoring** - Track system performance and resource usage
-   **Security Features** - Enhanced security configuration and rate limiting

### Additional Features

-   **Docker Ready** - Complete Docker setup with MariaDB and Redis
-   **Response Caching** - Optimized performance with Laravel Response Cache
-   **Modern UI** - Beautiful and responsive admin interface
-   **Command Line Tools** - Artisan commands for system health and backups
-   **Testing Framework** - PestPHP testing setup

## 📋 Requirements

-   PHP 8.2 or higher
-   Composer
-   Node.js 18+ and NPM
-   Docker & Docker Compose (for containerized deployment)
-   MySQL/MariaDB or SQLite

## 🛠️ Installation

### Method 1: Docker Setup (Recommended)

1. **Clone the repository**

```bash
git clone <repository-url>
cd filamentphp-3-admin-panel
```

2. **Start with Docker Compose**

```bash
docker-compose up -d
```

3. **Access the application**

-   Admin Panel: http://localhost:8100/admin
-   Default credentials: `admin@admin.com` / `P@ssw0rd`

The Docker setup includes:

-   **Laravel App**: Available on port 8100
-   **MariaDB**: Available on port 3307
-   **Redis**: Available on port 6379

### Method 2: Local Development Setup

1. **Clone and install dependencies**

```bash
git clone <repository-url>
cd filamentphp-3-admin-panel
composer install
npm install
```

2. **Environment setup**

```bash
cp .env.example .env
php artisan key:generate
```

3. **Configure your `.env` file**

```env
APP_NAME="Admin Panel"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_panel
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
SESSION_DRIVER=database
QUEUE_CONNECTION=database

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

4. **Database setup**

```bash
php artisan migrate 
php artisan shield:generate --all
php artisan db:seed
```

5. **Build assets and start development**

```bash
npm run build
php artisan optimize
php artisan icons:cache
php artisan serve
```

6. **Access the application**

-   Admin Panel: http://localhost:8000/admin
-   Default credentials: `admin@admin.com` / `P@ssw0rd`

## 🎯 Usage

### Admin Panel Features

#### User Management

-   Navigate to **Settings > Users** to manage system users
-   Create, edit, and delete users
-   Assign roles and permissions
-   View user activity and profiles

#### Role & Permission Management

-   Access **Shield > Roles** for role management
-   Create custom roles with specific permissions
-   Assign permissions for resources, pages, and widgets
-   Super Admin role has full system access

#### System Monitoring

-   **System Health** page provides real-time metrics:
    -   CPU and memory usage
    -   Database performance
    -   Cache status
    -   Security checks

#### Backup Management

-   **Backup Management** page allows:
    -   Create full system backups
    -   Database-only backups
    -   View backup history
    -   Download backup files

#### Profile Management

-   Users can edit their own profiles via the user menu
-   Update personal information
-   Change passwords
-   Manage browser sessions

### Command Line Tools

#### System Health Check

```bash
# Basic health check
php artisan system:health

# Detailed metrics
php artisan system:health --detailed

# JSON output
php artisan system:health --json

# Specific checks
php artisan system:health --check=database --check=cache
```

#### Backup Operations

```bash
# Create full backup
php artisan backup:run

# Database only backup
php artisan backup:run --type=database

# Backup with verification
php artisan backup:run --verify

# Backup with cleanup of old files
php artisan backup:run --cleanup
```

#### Development Commands

```bash
# Start development environment
composer run dev

# Run tests
composer run test

# Clear all caches
php artisan optimize:clear
```

## 🔧 Configuration

### Key Configuration Files

-   **`config/filament-shield.php`** - Role and permission settings
-   **`config/filament-users.php`** - User management configuration
-   **`config/backup.php`** - Backup system settings
-   **`config/security.php`** - Security and rate limiting
-   **`config/database.php`** - Database connections

### Important Environment Variables

```env
# Application
APP_NAME="Your Admin Panel"
APP_URL=http://your-domain.com

# Database
DB_CONNECTION=mariadb
DB_HOST=database
DB_DATABASE=admin_panel
DB_USERNAME=root
DB_PASSWORD=password

# Cache & Sessions
CACHE_STORE=redis
SESSION_DRIVER=database
REDIS_HOST=redis

# Security
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

# Backup
BACKUP_DISK=local
BACKUP_RETENTION_DAYS=30

# Rate Limiting
THROTTLE_REQUESTS=60
THROTTLE_DECAY_MINUTES=1
```

## 🏗️ Architecture

### Directory Structure

```
app/
├── Console/Commands/      # Custom artisan commands
├── Filament/
│   ├── Pages/            # Custom Filament pages
│   ├── Resources/        # Filament resources
│   └── Widgets/          # Dashboard widgets
├── Http/
│   ├── Controllers/      # HTTP controllers
│   └── Middleware/       # Custom middleware
├── Models/               # Eloquent models
├── Policies/             # Authorization policies
├── Providers/            # Service providers
└── Services/             # Business logic services
```

### Key Components

-   **FilamentPHP 3** - Admin panel framework
-   **Spatie Laravel Permission** - Role and permission management
-   **Laravel Response Cache** - Performance optimization
-   **TomatoPHP Filament Users** - Enhanced user management
-   **Filament Shield** - Permission management for Filament
-   **Filament Edit Profile** - User profile management

## 🔒 Security Features

-   **Role-based Access Control** - Granular permissions system
-   **Rate Limiting** - API and form submission protection
-   **Session Security** - Secure session handling
-   **Input Validation** - Comprehensive input sanitization
-   **CSRF Protection** - Built-in Laravel CSRF protection
-   **Password Hashing** - Secure password storage

## 📊 Monitoring & Health Checks

### System Metrics

-   CPU usage and load average
-   Memory consumption
-   Disk space utilization
-   Database connection status
-   Cache performance
-   Queue status

### Health Check Endpoints

The system includes automated health checks for:

-   Database connectivity
-   Cache functionality
-   File system permissions
-   Security configuration
-   Performance metrics

## 🔄 Backup System

### Backup Types

-   **Full Backup** - Complete system backup including database and files
-   **Database Backup** - Database only with compression
-   **Files Backup** - Application files and uploads
-   **Configuration Backup** - Environment and configuration files

### Backup Features

-   Automatic compression (configurable level 1-9)
-   Backup verification and integrity checks
-   Retention policy management
-   Storage to multiple disks
-   Backup restoration capabilities

## 🧪 Testing

The project includes a comprehensive testing setup using PestPHP:

```bash
# Run all tests
php artisan test

# Run specific test suite
vendor/bin/pest --testsuite=Feature
vendor/bin/pest --testsuite=Unit

# Run with coverage
vendor/bin/pest --coverage
```

## 🚀 Deployment

### Production Deployment

1. **Environment Setup**

```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false

# Configure secure sessions
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

# Set up database
DB_CONNECTION=mariadb
DB_HOST=your-db-host
```

2. **Optimization**

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

3. **Security Checklist**

-   Enable HTTPS
-   Configure firewall rules
-   Set up regular backups
-   Monitor system health
-   Update dependencies regularly

### Docker Production

```bash
# Build production image
docker build -t admin-panel:latest .

# Run with production settings
docker-compose -f docker-compose.prod.yml up -d
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

-   **Documentation**: Check the inline documentation and comments
-   **Health Monitoring**: Use `php artisan system:health` for diagnostics
-   **Logs**: Check `storage/logs/` for application logs
-   **Debug**: Enable `APP_DEBUG=true` for detailed error messages

---

**Built with ❤️ using Laravel 12 and FilamentPHP 3**

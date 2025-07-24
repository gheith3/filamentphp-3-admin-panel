# Laravel 12 + FilamentPHP 3 Admin Panel Starter Kit

A modern, production-ready Laravel 12 starter kit featuring FilamentPHP 3 admin panel, user management, role-based permissions, system monitoring, and comprehensive security features. Perfect for kickstarting your next Laravel project with a powerful admin interface.

## 🚀 Features

### Core Laravel 12 Features

-   **Laravel 12** - Latest Laravel framework with modern PHP 8.2+ features and performance improvements
-   **FilamentPHP 3** - Modern admin panel interface with rich components and beautiful UI
-   **Authentication System** - Complete authentication flow with login, registration, and password reset
-   **User Management** - Full CRUD operations for users with profile management
-   **Role & Permission System** - Advanced RBAC using Spatie Laravel Permission
-   **Modern Frontend** - Vite build system with Tailwind CSS 4.0

### Admin Panel Features

-   **Dashboard** - Comprehensive dashboard with widgets and system overview
-   **User Profile Management** - Self-service profile editing with avatar support
-   **Role & Permission Management** - Granular permission system for resources and pages
-   **System Monitoring** - Real-time system health checks and performance metrics
-   **Backup Management** - Automated backup system for database and files
-   **Security Features** - Enhanced security configuration and rate limiting

### Developer Experience

-   **Docker Ready** - Complete Docker setup with MariaDB and Redis
-   **Response Caching** - Optimized performance with Laravel Response Cache
-   **Testing Framework** - PestPHP testing setup with examples
-   **Code Quality** - Laravel Pint for code formatting
-   **Modern UI Components** - Beautiful and responsive admin interface

## 📦 Included Packages

### Core Admin Panel

-   **[FilamentPHP 3](https://filamentphp.com/)** (`filament/filament: ^3.3`)
    -   Modern admin panel framework
    -   Rich component library
    -   Beautiful, responsive UI
    -   Built-in form builder and table builder
    -   Dashboard with widgets

### User Management & Authentication

-   **[Filament Users](https://github.com/tomatophp/filament-users)** (`tomatophp/filament-users: ^2.0`)

    -   Enhanced user management interface
    -   User CRUD operations
    -   Advanced user filtering and search
    -   User activity tracking

-   **[Filament Edit Profile](https://github.com/joaopaulolndev/filament-edit-profile)** (`joaopaulolndev/filament-edit-profile: ^1.0`)
    -   Self-service profile editing
    -   Avatar management
    -   Password change functionality
    -   Browser session management

### Security & Permissions

-   **[Filament Shield](https://github.com/bezhanSalleh/filament-shield)** (`bezhansalleh/filament-shield: ^3.3`)
    -   Role and permission management for Filament
    -   Resource-level permissions
    -   Page-level permissions
    -   Widget-level permissions
    -   Built on Spatie Laravel Permission

### Performance & Optimization

-   **[Laravel Response Cache](https://github.com/spatie/laravel-responsecache)** (`spatie/laravel-responsecache: ^7.7`)
    -   Response caching for improved performance
    -   Configurable cache rules
    -   Cache invalidation strategies
    -   Performance monitoring

### Development Tools

-   **[Laravel Tinker](https://github.com/laravel/tinker)** (`laravel/tinker: ^2.10.1`) - Interactive PHP REPL
-   **[Laravel Pint](https://github.com/laravel/pint)** (`laravel/pint: ^1.13`) - Code style fixer
-   **[PestPHP](https://pestphp.com/)** (`pestphp/pest: ^3.8`) - Modern testing framework

## 📋 Requirements

-   **PHP 8.2+** - Laravel 12 minimum requirement
-   **Composer 2.0+** - Dependency management
-   **Node.js 18+** and NPM - Frontend build tools
-   **MySQL 8.0+** / **MariaDB 10.3+** / **PostgreSQL 13+** / **SQLite 3.35+**
-   **Redis** (optional, recommended for caching and sessions)
-   **Docker & Docker Compose** (optional, for containerized deployment)

## 🛠️ Quick Start

### Option 1: Use as Template (Recommended)

Create a new project based on this starter kit:

```bash
# Clone the repository
git clone <repository-url> my-new-project
cd my-new-project

# Remove git history and start fresh
rm -rf .git
git init
git add .
git commit -m "Initial commit from Laravel 12 starter kit"

# Set up your project
composer run setup
npm install && npm run build
```

### Option 2: Docker Development

Start developing immediately with Docker:

```bash
git clone <repository-url> my-new-project
cd my-new-project
docker-compose up -d

# Access admin panel at http://localhost:8100/admin
# Default login: admin@admin.com / P@ssw0rd
```

### Option 3: Local Development Setup

For local development without Docker:

```bash
# Clone and install dependencies
git clone <repository-url> my-laravel-app
cd my-laravel-app
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure your database in .env file
# Then run migrations and seeders
php artisan migrate
php artisan shield:generate --all
php artisan db:seed

# Build assets and start development
npm run build
php artisan serve

# Access your application at http://localhost:8000/admin
```

## ⚙️ Configuration

### Environment Variables

Key configuration options in your `.env` file:

```env
# Application
APP_NAME="Your App Name"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Sessions (Redis recommended)
CACHE_STORE=redis
SESSION_DRIVER=database
REDIS_HOST=127.0.0.1

# Mail Configuration
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="noreply@yourapp.com"
MAIL_FROM_NAME="${APP_NAME}"

# Security
SESSION_SECURE_COOKIE=false
CORS_ALLOWED_ORIGINS=
THROTTLE_REQUESTS=60
THROTTLE_DECAY_MINUTES=1

# Backup
BACKUP_DISK=local
BACKUP_DB_TIMEOUT=300
BACKUP_FILES_TIMEOUT=600
```

### Admin Panel Customization

Customize the FilamentPHP admin panel in `app/Providers/Filament/AdminPanelProvider.php`:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->colors([
            'primary' => Color::Blue, // Change primary color
        ])
        ->brandLogo(asset('images/logo.png')) // Add your logo
        // ... additional customizations
}
```

## 🎯 Pre-built Features

### Authentication System

-   **Login/Logout** - Secure authentication flow
-   **Password Reset** - Email-based password recovery
-   **Remember Me** - Persistent sessions
-   **Session Management** - Multiple device handling
-   **Rate Limiting** - Brute force protection

### User Management

-   **User CRUD** - Complete user management interface
-   **Profile Management** - Self-service profile editing
-   **Avatar Upload** - User profile pictures
-   **Role Assignment** - Assign roles to users
-   **Permission Management** - Granular permissions

### Role-Based Access Control (RBAC)

-   **Roles** - Create and manage user roles
-   **Permissions** - Granular permission system
-   **Resource Protection** - Secure admin resources
-   **Page Protection** - Protect custom pages
-   **Widget Protection** - Secure dashboard widgets

### Dashboard & Monitoring

-   **System Health Widget** - Real-time system metrics
-   **Performance Monitoring** - Track application performance
-   **Database Health** - Monitor database status
-   **Cache Status** - Check cache functionality
-   **Memory Usage** - Track memory consumption

### Backup System

-   **Database Backups** - Automated database backups
-   **File Backups** - Application file backups
-   **Backup Verification** - Integrity checks
-   **Backup Management** - View and manage backups
-   **Automated Cleanup** - Remove old backups

### Security Features

-   **Rate Limiting** - Multiple rate limiting strategies
-   **CSRF Protection** - Built-in CSRF protection
-   **Input Validation** - Comprehensive input sanitization
-   **Security Headers** - Security-focused HTTP headers
-   **Session Security** - Secure session configuration

## 🔧 What's Included

### File Structure

```
app/
├── Filament/
│   ├── Pages/           # Custom admin pages
│   ├── Resources/       # Admin resources
│   └── Widgets/         # Dashboard widgets
├── Http/
│   ├── Controllers/     # HTTP controllers
│   └── Middleware/      # Custom middleware
├── Models/              # Eloquent models
├── Policies/            # Authorization policies
├── Providers/           # Service providers
└── Services/            # Business logic services
```

### Key Configuration Files

-   `config/filament-shield.php` - Permission settings
-   `config/filament-users.php` - User management configuration
-   `config/backup.php` - Backup system settings
-   `config/security.php` - Security and rate limiting
-   `config/responsecache.php` - Response cache configuration

## 🎯 Usage & Customization

### Creating New Admin Resources

Generate a new Filament resource for your models:

```bash
php artisan make:filament-resource Product

# With pages
php artisan make:filament-resource Product --generate

# With soft deletes
php artisan make:filament-resource Product --soft-deletes
```

### Adding New Permissions

Add permissions for your new resources:

```bash
php artisan shield:generate --resource=ProductResource
```

### Custom Admin Pages

Create custom admin pages:

```bash
php artisan make:filament-page Settings
```

### Dashboard Widgets

Create dashboard widgets:

```bash
php artisan make:filament-widget StatsOverview --stats-overview
```

### Adding Your Models and Resources

**Create a new model and resource:**

```bash
# Create model with migration
php artisan make:model Product -m

# Create Filament resource
php artisan make:filament-resource Product --generate

# Add permissions for the resource
php artisan shield:generate --resource=ProductResource
```

**Example Product model:**

```php
// app/Models/Product.php
class Product extends Model
{
    protected $fillable = ['name', 'description', 'price', 'is_active'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
```

## 🔧 Development Workflow

### Daily Development Commands

```bash
# Start development environment
composer run dev

# Run tests
composer run test

# Check code style
./vendor/bin/pint

# Clear caches (if needed)
php artisan optimize:clear
```

### Key Artisan Commands

**System Commands:**

```bash
php artisan system:health              # Check system health
php artisan system:health --detailed   # Detailed health check
php artisan system:health --json       # JSON output
```

**Backup Commands:**

```bash
php artisan backup:run                 # Create full backup
php artisan backup:run --type=database # Database only
php artisan backup:run --verify        # Backup with verification
php artisan backup:run --cleanup       # Backup with cleanup
```

**Development Commands:**

```bash
composer run dev                       # Start development environment
composer run test                      # Run test suite
composer run fresh                     # Fresh migration with seed
composer run setup                     # Complete setup
```

### Database Changes

```bash
# Create migration
php artisan make:migration create_products_table

# Run migrations
php artisan migrate

# If you need to start fresh
composer run fresh
```

## 🧪 Testing

The starter kit includes PestPHP for testing:

```bash
# Run all tests
php artisan test

# Run specific test suite
./vendor/bin/pest --group=feature
./vendor/bin/pest --group=unit

# Run with coverage
./vendor/bin/pest --coverage
```

### Test Structure

```
tests/
├── Feature/              # Integration tests
├── Unit/                 # Unit tests
├── Pest.php             # Pest configuration
└── TestCase.php         # Base test class
```

### Adding Tests

```bash
# Create feature test
php artisan make:test ProductTest

# Create unit test
php artisan make:test ProductUnitTest --unit

# Run specific tests
./vendor/bin/pest --filter=Product
```

## 🛡️ Security

### Built-in Security Features

-   ✅ CSRF Protection
-   ✅ SQL Injection Prevention
-   ✅ XSS Protection
-   ✅ Rate Limiting
-   ✅ Input Validation
-   ✅ Session Security
-   ✅ Password Hashing
-   ✅ Secure Headers

### Production Security Checklist

-   [ ] Set `APP_ENV=production`
-   [ ] Set `APP_DEBUG=false`
-   [ ] Use HTTPS (`SESSION_SECURE_COOKIE=true`)
-   [ ] Configure `CORS_ALLOWED_ORIGINS`
-   [ ] Set up proper database credentials
-   [ ] Configure mail settings
-   [ ] Set up backup system
-   [ ] Configure error logging
-   [ ] Update default admin credentials

## 🚀 Deployment

### Production Optimization

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Build and optimize
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Set production environment
APP_ENV=production
APP_DEBUG=false
```

### Docker Production

```bash
# Build production image
docker build -t your-app:latest .

# Run with production settings
docker-compose -f docker-compose.prod.yml up -d
```

### Cloud Platforms

-   **Laravel Forge** - Recommended for easy deployment
-   **Laravel Vapor** - Serverless deployment on AWS
-   **DigitalOcean App Platform** - Simple container deployment
-   **Heroku** - Quick deployment with buildpacks

## 📦 Package Management

### Adding New Packages

```bash
# Add a new package
composer require vendor/package-name

# For Filament-specific packages
composer require filament/package-name

# Update all packages
composer update
```

### Recommended Additional Packages

-   **Laravel Sanctum** - API authentication
-   **Laravel Horizon** - Queue management
-   **Laravel Telescope** - Debugging assistant
-   **Spatie Media Library** - File management
-   **Laravel Excel** - Excel import/export

## 🎨 UI Customization

### Styling Framework

-   **Tailwind CSS 4.0** - Utility-first CSS framework
-   **Responsive Design** - Mobile-first responsive layout
-   **Dark Mode Support** - Built-in dark mode toggle
-   **Custom Color Scheme** - Configurable brand colors
-   **Icon Library** - Heroicons integration

### FilamentPHP Components

-   **Forms** - Rich form builder with validation
-   **Tables** - Advanced data tables with filtering
-   **Notifications** - Toast notifications and alerts
-   **Modals** - Interactive modal dialogs
-   **Widgets** - Dashboard widgets and charts
-   **Navigation** - Sidebar and breadcrumb navigation

### Component Customization

-   Modify Tailwind configuration in `tailwind.config.js`
-   Customize Filament colors in `AdminPanelProvider`
-   Add custom CSS in `resources/css/app.css`
-   Create custom Blade views as needed
-   Publish Filament views: `php artisan vendor:publish --tag=filament-views`

## 🚀 Performance Features

### Caching Strategy

-   **Response Caching** - Full page caching
-   **Database Query Caching** - Query result caching
-   **Redis Integration** - Fast in-memory caching
-   **View Caching** - Compiled view caching
-   **Configuration Caching** - Production optimization

### Optimization Features

-   **Lazy Loading** - Efficient resource loading
-   **Database Indexing** - Optimized database queries
-   **Asset Compilation** - Minified CSS/JS
-   **Image Optimization** - Compressed image assets
-   **HTTP/2 Support** - Modern protocol support

## 📚 Documentation & Learning

### Package Documentation

-   [FilamentPHP Documentation](https://filamentphp.com/docs)
-   [Laravel 12 Documentation](https://laravel.com/docs/12.x)
-   [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission/)

### Video Tutorials

-   Laravel Daily YouTube Channel
-   Filament Daily Content
-   Laracasts Laravel Content

### Community

-   Laravel Discord
-   Filament Discord
-   Reddit r/laravel

## 🤝 Contributing

If you find issues or want to contribute improvements:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

-   [Laravel](https://laravel.com/) - The PHP Framework For Web Artisans
-   [FilamentPHP](https://filamentphp.com/) - The elegant TALL stack admin panel
-   [Spatie](https://spatie.be/) - Excellent Laravel packages
-   All the amazing package contributors

---

**Ready to build something amazing? Clone this starter kit and get started with Laravel 12 + FilamentPHP 3! 🚀**

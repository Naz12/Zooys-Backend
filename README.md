# 🚀 Zooys Backend - Laravel API

A comprehensive Laravel-based backend API for a SaaS platform offering AI-powered tools and subscription management. Built with Laravel 12, featuring user authentication, subscription management, payment processing, and admin panel functionality.

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Documentation](#-api-documentation)
- [Project Structure](#-project-structure)
- [Development](#-development)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### 🔐 Authentication & Authorization
- **User Authentication**: Laravel Sanctum-based API authentication
- **Admin Authentication**: Separate admin authentication system
- **Password Reset**: Email-based password reset functionality
- **Session Management**: Secure session handling for both users and admins

### 👥 User Management
- **User Registration & Login**: Complete user lifecycle management
- **User Status Management**: Active, inactive, and suspended user states
- **Bulk Operations**: Bulk activate, deactivate, and delete users
- **User Analytics**: Usage tracking and activity monitoring

### 💳 Subscription & Payment System
- **Subscription Plans**: Flexible subscription plan management
- **Stripe Integration**: Complete payment processing with Stripe
- **Usage Tracking**: Monitor user usage against plan limits
- **Subscription History**: Track subscription changes and renewals

### 🛠️ AI Tools (Framework Ready)
- **YouTube Summarizer**: Video content summarization
- **PDF Summarizer**: Document summarization
- **AI Writer**: Content generation tool
- **Math Solver**: Mathematical problem solving
- **Flashcard Generator**: Educational flashcard creation
- **Diagram Generator**: Visual diagram creation

### 📊 Admin Panel
- **Dashboard Analytics**: Comprehensive KPI monitoring
- **User Management**: Complete user administration
- **Plan Management**: Subscription plan configuration
- **Subscription Management**: Subscription lifecycle control
- **Tool Management**: AI tool configuration and monitoring
- **Visitor Analytics**: Website traffic and user behavior analysis
- **System Administration**: Health monitoring and maintenance tools

### 🔒 Security Features
- **CSRF Protection**: Cross-site request forgery protection
- **Rate Limiting**: API rate limiting for security
- **Input Validation**: Comprehensive request validation
- **Password Hashing**: Secure password storage
- **Token-based Authentication**: Secure API access
- **Usage Limit Middleware**: Prevent abuse of resources

## 🛠️ Tech Stack

### Backend
- **Laravel 12.x**: PHP framework
- **Laravel Sanctum**: API authentication
- **Stripe PHP SDK**: Payment processing
- **SQLite**: Database (development)
- **Laravel Mail**: Email functionality

### Frontend Assets
- **Vite**: Build tool
- **Tailwind CSS**: Styling framework
- **Alpine.js**: Lightweight JavaScript framework
- **Axios**: HTTP client

### Development Tools
- **Laravel Pint**: Code formatting
- **Pest PHP**: Testing framework
- **Laravel Pail**: Log viewing
- **Laravel Sail**: Docker development environment

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- SQLite (or MySQL/PostgreSQL for production)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/zooys_backend_laravel.git
   cd zooys_backend_laravel
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start development server**
   ```bash
   composer run dev
   ```

The application will be available at `http://localhost:8000`

## ⚙️ Configuration

### Environment Variables

Create a `.env` file with the following configuration:

```env
APP_NAME="Zooys Backend"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

STRIPE_KEY=your-stripe-publishable-key
STRIPE_SECRET=your-stripe-secret-key
STRIPE_WEBHOOK_SECRET=your-stripe-webhook-secret

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### Stripe Configuration

1. Create a Stripe account at [stripe.com](https://stripe.com)
2. Get your API keys from the Stripe dashboard
3. Set up webhook endpoints for payment processing
4. Configure webhook events: `checkout.session.completed`, `invoice.payment_succeeded`, `invoice.payment_failed`

## 📚 API Documentation

### Authentication Endpoints

#### User Registration
```http
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
}
```

#### User Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

#### Get Current User
```http
GET /api/user
Authorization: Bearer {token}
```

### Subscription Endpoints

#### Get Current Subscription
```http
GET /api/subscription
Authorization: Bearer {token}
```

#### Get Subscription History
```http
GET /api/subscription/history
Authorization: Bearer {token}
```

#### Get Usage Statistics
```http
GET /api/usage
Authorization: Bearer {token}
```

### Tool Endpoints

#### YouTube Summarizer
```http
POST /api/youtube/summarize
Authorization: Bearer {token}
Content-Type: application/json

{
    "video_url": "https://youtube.com/watch?v=abc123",
    "language": "en",
    "mode": "detailed"
}
```

#### AI Writer
```http
POST /api/writer/run
Authorization: Bearer {token}
Content-Type: application/json

{
    "prompt": "Write a blog post about AI",
    "mode": "creative"
}
```

### Admin Endpoints

#### Admin Login
```http
POST /api/admin/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "admin123"
}
```

#### Dashboard Analytics
```http
GET /api/admin/dashboard
Authorization: Bearer {admin_token}
```

#### User Management
```http
GET /api/admin/users
Authorization: Bearer {admin_token}
```

For complete API documentation, see:
- [User API Documentation](user_api_updated.md)
- [Admin API Documentation](admin_api.md)
- [API Analysis](api.md)

## 📁 Project Structure

```
zooys_backend_laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Client/          # User-facing API controllers
│   │   │   │   └── Admin/           # Admin API controllers
│   │   │   └── Middleware/          # Custom middleware
│   │   └── Requests/                # Form request validation
│   ├── Mail/                        # Email templates
│   ├── Models/                      # Eloquent models
│   └── Providers/                   # Service providers
├── database/
│   ├── migrations/                  # Database migrations
│   └── seeders/                     # Database seeders
├── routes/
│   ├── api.php                      # API routes
│   └── web.php                      # Web routes
├── public/                          # Public assets
├── resources/
│   ├── js/                          # JavaScript assets
│   └── views/                       # Blade templates
└── tests/                           # Test files
```

## 🛠️ Development

### Development Commands

```bash
# Start development server with all services
composer run dev

# Run tests
composer test

# Code formatting
./vendor/bin/pint

# View logs
php artisan pail

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Management

```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed database
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Queue Management

```bash
# Start queue worker
php artisan queue:work

# Process failed jobs
php artisan queue:retry all
```

## 🧪 Testing

The project uses Pest PHP for testing:

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/pest tests/Feature/ExampleTest.php

# Run tests with coverage
./vendor/bin/pest --coverage
```

### Test Structure
- **Feature Tests**: API endpoint testing
- **Unit Tests**: Individual component testing
- **Integration Tests**: Cross-component testing

## 🚀 Deployment

### Production Setup

1. **Server Requirements**
   - PHP 8.2+
   - Composer
   - Node.js & NPM
   - MySQL/PostgreSQL
   - Redis (optional, for caching)

2. **Environment Configuration**
   ```bash
   # Set production environment
   APP_ENV=production
   APP_DEBUG=false
   
   # Configure database
   DB_CONNECTION=mysql
   DB_HOST=your-db-host
   DB_DATABASE=your-db-name
   DB_USERNAME=your-db-user
   DB_PASSWORD=your-db-password
   ```

3. **Deployment Steps**
   ```bash
   # Install dependencies
   composer install --optimize-autoloader --no-dev
   npm install && npm run build
   
   # Run migrations
   php artisan migrate --force
   
   # Cache configuration
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   
   # Set permissions
   chmod -R 755 storage bootstrap/cache
   ```

### Docker Deployment

The project includes Laravel Sail for Docker development:

```bash
# Start Docker environment
./vendor/bin/sail up -d

# Run commands in Docker
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
```

## 📊 Current Status

### ✅ Completed Features (85%)
- User authentication system
- Admin authentication system
- Subscription management
- Payment processing (Stripe)
- User management (CRUD)
- Plan management
- Tool framework (6 tools)
- Admin dashboard
- Visitor analytics
- Security middleware

### 🚧 In Progress
- AI tool implementations (currently dummy data)
- Advanced analytics
- Performance optimization

### 📋 Roadmap
- Real AI service integrations
- Advanced reporting
- Mobile API support
- Webhook system
- Advanced caching
- Microservices architecture

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow Laravel coding standards
- Write tests for new features
- Update documentation
- Use meaningful commit messages
- Ensure all tests pass

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the API analysis files

## 🙏 Acknowledgments

- Laravel framework and community
- Stripe for payment processing
- All contributors and testers

---

**Built with ❤️ using Laravel**
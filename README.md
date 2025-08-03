# 💕 Lovebirds Dating - Backend API

**Lovebirds Dating Backend** is a comprehensive Laravel-based REST API that powers the Lovebirds Dating mobile application. It provides robust authentication, matching algorithms, real-time messaging, and integrated shopping functionality.

## 🌟 Features

### 🔐 Authentication & Security
- **JWT Authentication**: Secure token-based authentication
- **Email Verification**: Mandatory email verification after registration
- **Password Reset**: Secure password reset with verification codes
- **Content Moderation**: Automated content filtering and manual review
- **GDPR Compliance**: Privacy-focused data handling

### 💕 Dating API Endpoints
- **User Management**: Profile creation, updates, and discovery
- **Matching Algorithm**: Location-based and preference-based matching
- **Photo Gallery**: Multi-photo upload and management
- **Like/Dislike System**: Swipe functionality with match detection
- **Advanced Filtering**: Search by age, location, interests, lifestyle

### 💬 Real-time Messaging
- **Chat Management**: Create, list, and manage conversations
- **Message Types**: Text, audio, video, images, files, location
- **Real-time Features**: Typing indicators, read receipts, online status
- **File Handling**: Secure media upload and sharing
- **Message Reactions**: Emoji reactions and message interactions

### 🛍️ Shopping Integration
- **Product Management**: CRUD operations for products
- **Inventory Control**: Stock tracking and availability
- **Shopping Cart**: Add, remove, and manage cart items
- **Order Processing**: Dual options - contact seller or checkout
- **Payment Integration**: Stripe payment processing

### 💳 Subscription Management
- **Subscription Plans**: Weekly, monthly, and quarterly plans
- **Payment Processing**: Secure Stripe integration
- **Access Control**: Subscription-based app access
- **Plan Management**: Upgrade, downgrade, and cancellation

## 🏗️ Technical Architecture

### Core Technologies
- **Framework**: Laravel 10.x
- **PHP Version**: 8.1+
- **Database**: MySQL 8.0+
- **Authentication**: JWT (tymon/jwt-auth)
- **File Storage**: Local/Cloud storage support
- **Queue System**: Redis/Database queues for background jobs

### Key Laravel Packages
```json
{
  "tymon/jwt-auth": "JWT authentication",
  "encore/laravel-admin": "Admin panel",
  "stripe/stripe-php": "Payment processing",
  "intervention/image": "Image processing",
  "pusher/pusher-php-server": "Real-time features"
}
```

## 🚀 Installation & Setup

### 1. Environment Setup
```bash
# Clone repository
git clone <repository-url>
cd lovebirds-api

# Install dependencies
composer install

# Environment configuration
cp .env.example .env
php artisan key:generate
```

### 2. Database Configuration
```bash
# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lovebirds_dating
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed
```

### 3. JWT Setup
```bash
# Generate JWT secret
php artisan jwt:secret

# Publish JWT config
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

### 4. Storage Setup
```bash
# Create storage link
php artisan storage:link

# Set proper permissions
chmod -R 775 storage bootstrap/cache
```

### 5. Start Development Server
```bash
php artisan serve
# API will be available at http://localhost:8000
```

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── ApiController.php           # Main API controller
│   │   ├── DynamicCrudController.php   # Generic CRUD operations
│   │   └── ModerationController.php    # Content moderation
│   └── Middleware/
│       └── JwtMiddleware.php           # JWT authentication middleware
├── Models/
│   ├── User.php                        # User model with dating features
│   ├── ChatMessage.php                 # Chat messaging
│   ├── Product.php                     # Shopping products
│   └── Subscription.php                # User subscriptions
└── Traits/                            # Reusable model traits

routes/
├── api.php                            # API routes
└── web.php                            # Web routes

database/
├── migrations/                        # Database schema
└── seeders/                          # Sample data
```

## 🔧 API Documentation

### Authentication Endpoints
```http
POST /auth/register                    # User registration
POST /auth/login                      # User login
POST /auth/password-reset             # Reset password
POST /auth/request-password-reset-code # Request reset code
GET  /me                              # Get current user
```

### Dating Features
```http
GET  /api/users                       # Get potential matches
POST /api/users                       # Update user profile
POST /api/likes                       # Like/dislike users
GET  /api/matches                     # Get user matches
POST /api/photos                      # Upload profile photos
POST /api/report-user                 # Report inappropriate user
```

### Messaging System
```http
POST /chat-start                      # Start new conversation
POST /chat-send                       # Send message
GET  /chat-heads                      # Get chat list
GET  /chat-messages                   # Get conversation messages
POST /chat-mark-as-read               # Mark messages as read
POST /post-media-upload               # Upload media files
```

### Shopping Features
```http
GET  /products-1                      # Get products list
POST /product-create                  # Create new product
POST /products-delete                 # Delete product
POST /api/cart                        # Shopping cart operations
POST /api/orders                      # Order management
```

### Dynamic CRUD Operations
```http
GET  /dynamic-list                    # Generic list endpoint
POST /dynamic-save                    # Generic save endpoint
POST /dynamic-delete                  # Generic delete endpoint
```

## 🧪 Testing

### API Testing Files
The project includes comprehensive testing files:

```bash
# Quick authentication test
php quick_auth_test.php

# Content moderation testing
php test_moderation.php
php test_moderation_authenticated.php
php test_moderation_comprehensive.php

# Debug authentication
php debug_auth.php
```

### Running Laravel Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Manual API Testing
```bash
# Test registration
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password","first_name":"John","last_name":"Doe"}'

# Test login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

## 🔒 Security Features

### Content Moderation
- **Automated Filtering**: Real-time content analysis
- **Manual Review**: Admin panel for content review
- **User Reporting**: Community-driven moderation
- **Image Analysis**: Inappropriate image detection

### Data Protection
- **Input Validation**: Comprehensive request validation
- **SQL Injection Protection**: Eloquent ORM protection
- **XSS Prevention**: Output sanitization
- **CSRF Protection**: Cross-site request forgery protection
- **Rate Limiting**: API request throttling

## 🌍 Configuration

### Environment Variables
```env
# App Configuration
APP_NAME="Lovebirds Dating"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-host
DB_PORT=3306
DB_DATABASE=lovebirds_dating
DB_USERNAME=your-username
DB_PASSWORD=your-password

# JWT Configuration
JWT_SECRET=your-jwt-secret
JWT_TTL=60

# Payment Configuration
STRIPE_KEY=your-stripe-public-key
STRIPE_SECRET=your-stripe-secret-key

# File Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-mail-username
MAIL_PASSWORD=your-mail-password
```

## 🚀 Deployment

### Production Deployment
```bash
# Optimize for production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up cron job for scheduled tasks
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

# Configure web server (Nginx/Apache)
# Point document root to /public directory
```

### Database Optimization
```bash
# Create indexes for performance
php artisan migrate:refresh --seed
php artisan optimize:clear
```

## 📊 Performance & Monitoring

### Caching Strategy
- **Redis**: Session and cache storage
- **Database Queries**: Eloquent query optimization
- **API Responses**: Response caching for read-heavy endpoints
- **File Caching**: Static asset caching

### Logging & Monitoring
- **Application Logs**: Comprehensive error logging
- **API Analytics**: Request/response monitoring
- **Performance Metrics**: Database query optimization
- **Error Tracking**: Real-time error notifications

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create a feature branch
3. Implement changes with tests
4. Submit pull request
5. Code review and merge

### Code Standards
- **PSR-12**: PHP coding standards
- **Laravel Best Practices**: Framework conventions
- **Database Naming**: Consistent naming conventions
- **API Design**: RESTful API principles

## 📞 Support & Maintenance

### Regular Maintenance
- **Database Backups**: Automated daily backups
- **Security Updates**: Regular dependency updates
- **Performance Monitoring**: Continuous optimization
- **Log Rotation**: Automated log management

### Support Channels
- **Technical Issues**: GitHub Issues
- **Security Concerns**: security@lovebirds-dating.com
- **General Support**: support@lovebirds-dating.com

---

**Backend API for meaningful connections** 💕

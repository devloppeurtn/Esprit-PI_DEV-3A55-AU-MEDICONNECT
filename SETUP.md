# MediConnect - Setup Guide

## Project Information
- **Project**: PIDEV – 3rd Year Engineering Program
- **Institution**: Esprit School of Engineering
- **Academic Year**: 2025–2026
- **Repository**: https://github.com/devloppeurtn/Esprit-PI_DEV-3A55-AU-MEDICONNECT

## Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 8.0 (or Docker)
- Docker & Docker Compose (recommended)
- Node.js (optional, for asset compilation)

## Installation Steps

### 1. Clone the Repository
```bash
git clone https://github.com/devloppeurtn/Esprit-PI_DEV-3A55-AU-MEDICONNECT.git
cd Esprit-PI_DEV-3A55-AU-MEDICONNECT
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment Variables
Copy `.env` to `.env.local` and configure your secrets:
```bash
cp .env .env.local
```

Edit `.env.local` and add your real credentials:
```env
# Database
DATABASE_URL="mysql://root:root@127.0.0.1:3306/mediconnect?serverVersion=8.0.32&charset=utf8mb4"

# Mailer (Brevo SMTP)
MAILER_DSN=smtp://your-login:your-smtp-key@smtp-relay.brevo.com:587
MAILER_FROM="MediConnect <your-email@example.com>"

# Stripe
STRIPE_SECRET_KEY=sk_test_your_stripe_secret_key
STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_publishable_key

# AWS Rekognition (Face ID)
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key

# OCR.space
OCR_SPACE_API_KEY=your_ocr_api_key

# Google OAuth (optional)
GOOGLE_OAUTH_CLIENT_ID=your_google_client_id
GOOGLE_OAUTH_CLIENT_SECRET=your_google_client_secret
```

### 4. Start Database with Docker
```bash
docker-compose up -d database
```

Wait a few seconds for MySQL to initialize.

### 5. Run Database Migrations
```bash
php bin/console doctrine:migrations:migrate
```

### 6. (Optional) Start Mercure for Real-time Features
```bash
docker-compose up -d mercure
```

### 7. Start Symfony Development Server
```bash
symfony server:start
```

Or without Symfony CLI:
```bash
php -S 127.0.0.1:8000 -t public
```

### 8. Access the Application
Open your browser: **http://127.0.0.1:8000**

## Quick Start (All Commands)
```bash
# Install dependencies
composer install

# Configure environment
cp .env .env.local
# Edit .env.local with your credentials

# Start Docker services
docker-compose up -d

# Run migrations
php bin/console doctrine:migrations:migrate

# Start server
symfony server:start
```

## Features
- **Authentication**: Login, Registration, Password Reset
- **Two-Factor Authentication (2FA)**: Google Authenticator
- **OAuth**: Google Sign-In
- **Face ID**: AWS Rekognition for facial recognition
- **Payment**: Stripe integration
- **Real-time**: Mercure for live notifications
- **Email**: Brevo SMTP integration
- **OCR**: Document scanning with OCR.space
- **Admin Panel**: User management, analytics
- **Medical Records**: Patient management system
- **Appointments**: Booking and scheduling
- **E-commerce**: Product catalog and orders
- **Events**: Event management system

## Docker Services
- **MySQL**: Port 3306 (database)
- **Mercure**: Port 1337 (real-time hub)

## Testing
```bash
# Run PHPUnit tests
php bin/phpunit

# Run PHPStan static analysis
vendor/bin/phpstan analyse
```

## Troubleshooting

### Database Connection Error
Make sure MySQL is running:
```bash
docker-compose ps
```

If not running:
```bash
docker-compose up -d database
```

### Port Already in Use
If port 8000 is busy, use a different port:
```bash
php -S 127.0.0.1:8080 -t public
```

### Clear Cache
```bash
php bin/console cache:clear
```

## Project Structure
```
├── config/              # Configuration files
├── migrations/          # Database migrations
├── public/              # Web root (index.php, assets)
├── src/
│   ├── Controller/      # Controllers
│   ├── Entity/          # Doctrine entities
│   ├── Repository/      # Database repositories
│   ├── Service/         # Business logic services
│   └── Security/        # Authentication & authorization
├── templates/           # Twig templates
├── tests/               # PHPUnit tests
├── var/                 # Cache, logs
└── vendor/              # Composer dependencies
```

## Contributors
- Email: isra.zguir@ieee.org

## License
Proprietary - Esprit School of Engineering

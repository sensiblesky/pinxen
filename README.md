# Admin Panel Template - Laravel Application

A comprehensive, pre-built admin panel template built with Laravel 12, featuring a modern UI, role-based access control, two-factor authentication, email verification, and extensive system configuration options.

## 🚀 Features

### Core Features
- **Role-Based Access Control (RBAC)**: Separate admin and client interfaces
- **Two-Factor Authentication (2FA)**: Google Authenticator support with recovery codes
- **Email Verification**: OTP-based email verification system
- **User Management**: Complete user CRUD operations with soft delete
- **Login Activity Tracking**: Monitor user login sessions and activities
- **Secure Image Serving**: Encrypted avatar/image serving for enhanced security

### Authentication & Security
- **Custom Login/Registration Control**: Enable/disable user registration and login
- **Force Email Verification**: Require email verification before account access
- **Force 2FA**: Require two-factor authentication for all users
- **Password Reset**: Secure password reset with email notifications
- **Session Management**: Track and manage user sessions

### System Configuration
- **General Configuration**: App name, email, timezone, language settings
- **Communication Channels**: SMTP configuration with database storage
- **Payment Gateway**: Payment gateway configuration
- **Authentication & SSO**: Control authentication settings
- **reCAPTCHA**: Google reCAPTCHA integration
- **Storage Settings**: File storage configuration (local/S3)
- **Cache Management**: Cache configuration and management
- **FAQ Management**: Create and manage FAQs with categories and icons

### User Features
- **Profile Management**: Update profile information and avatar
- **Account Security**: Change password, manage 2FA settings
- **Recovery Codes**: Generate and regenerate 2FA recovery codes
- **Support & Help**: Public FAQ page with search functionality

### Admin Panel Features
- **Dashboard**: Admin overview dashboard
- **Users Management**: View, create, edit, delete, and manage user accounts
- **System Configuration**: Comprehensive system settings management
- **API Management**: API management interface (ready for implementation)
- **FAQ Management**: Create, edit, and manage FAQs

## 📋 Requirements

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL/PostgreSQL/SQLite
- Web server (Apache/Nginx) or PHP built-in server

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd pingxen
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   - Update `.env` with your database credentials:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=your_database
     DB_USERNAME=your_username
     DB_PASSWORD=your_password
     ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed initial data (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Build assets**
   ```bash
   npm run build
   # Or for development:
   npm run dev
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

## ⚙️ Configuration

### Initial Setup

1. **Create Admin User**
   - Register a new user or use a database seeder
   - Update the user's `role` field to `1` in the database to make them an admin

2. **Configure SMTP** (for email functionality)
   - Navigate to `/panel/comm-channels`
   - Enter your SMTP settings
   - Test email sending

3. **System Configuration**
   - Navigate to `/panel/system-configuration`
   - Configure app name, email, timezone, and other settings

### User Roles

- **Admin (Role: 1)**: Full access to admin panel and all features
- **Client (Role: 2)**: Access to client dashboard and profile features

### Authentication Settings

Configure authentication behavior in `/panel/auth-sso`:
- **User Registration**: Enable/disable new user registration
- **User Login**: Enable/disable user login
- **Force Email Verification**: Require email verification for all users
- **Force Two Factor Authentication**: Require 2FA for all users

## 🎨 UI/UX Features

- **Modern Design**: Clean, responsive admin panel interface
- **Role-Based Navigation**: Different menu structures for admin and client users
- **Active Menu Highlighting**: Smart menu state management
- **Responsive Layout**: Mobile-friendly design
- **Icon Support**: Font icons for better visual experience

## 📧 Email Features

- **SMTP Configuration**: Database-stored SMTP settings
- **Email Verification OTP**: Time-stamped OTP emails
- **Password Reset**: Custom password reset emails
- **Email Templates**: Beautiful, responsive email layouts

## 🔐 Security Features

- **Encrypted Storage**: Sensitive data (passwords, 2FA secrets) are encrypted
- **Secure Image Serving**: Encrypted paths for avatar/image access
- **Session Tracking**: Monitor active sessions and login activities
- **CSRF Protection**: Laravel's built-in CSRF protection
- **Password Hashing**: Bcrypt password hashing
- **2FA Recovery Codes**: Secure recovery code generation and management

## 📁 Project Structure

```
pingxen/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/          # Authentication controllers
│   │   │   ├── Panel/         # Admin panel controllers
│   │   │   └── Account/        # User account controllers
│   │   ├── Middleware/        # Custom middleware
│   │   └── Requests/          # Form request validation
│   ├── Mail/                  # Mailable classes
│   ├── Models/                # Eloquent models
│   ├── Services/              # Business logic services
│   └── Notifications/         # Notification classes
├── database/
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
├── resources/
│   ├── views/                 # Blade templates
│   │   ├── auth/              # Authentication views
│   │   ├── panel/             # Admin panel views
│   │   ├── layouts/           # Layout components
│   │   └── emails/             # Email templates
│   ├── css/                   # Stylesheets
│   └── js/                    # JavaScript files
└── routes/
    ├── web.php                # Web routes
    ├── auth.php               # Authentication routes
    └── panel.php              # Admin panel routes
```

## 🛠️ Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

### Development Server with Hot Reload
```bash
composer run dev
```

## 📝 Key Routes

### Public Routes
- `/` - Welcome page
- `/login` - Login page
- `/register` - Registration page
- `/forgot-password` - Password reset request
- `/reset-password` - Password reset form
- `/support` - Public support/FAQ page

### Authenticated Routes
- `/dashboard` - Client dashboard
- `/profile` - User profile
- `/account/security/password` - Change password
- `/account/security/two-factor` - 2FA management
- `/verify-email-otp` - Email verification

### Admin Routes
- `/panel` - Admin dashboard
- `/panel/users` - User management
- `/panel/system-configuration` - System settings
- `/panel/comm-channels` - SMTP configuration
- `/panel/auth-sso` - Authentication settings
- `/panel/faqs` - FAQ management

## 🔄 Workflow

### Login Flow
1. User enters credentials
2. If 2FA enabled → Redirect to 2FA verification
3. If email verification required → Redirect to email verification
4. Role-based redirect:
   - Admin → `/panel`
   - Client → `/dashboard`

### Registration Flow
1. User registers
2. If email verification required → Redirect to email verification
3. Otherwise → Redirect to `/dashboard`

## 📦 Dependencies

### PHP Packages
- `laravel/framework` ^12.0
- `pragmarx/google2fa-laravel` ^2.3 - Two-factor authentication
- `bacon/bacon-qr-code` ^3.0 - QR code generation
- `league/flysystem-aws-s3-v3` ^3.0 - AWS S3 storage support

### JavaScript Packages
- See `package.json` for frontend dependencies

## 🐛 Troubleshooting

### Email Not Sending
- Check SMTP configuration in `/panel/comm-channels`
- Verify SMTP credentials are correct
- Check application logs: `storage/logs/laravel.log`

### 2FA Not Working
- Ensure `pragmarx/google2fa-laravel` is installed
- Check that user has properly set up 2FA
- Verify time synchronization on server

### Image Upload Issues
- Check file permissions on `storage/app/public`
- Verify storage link: `php artisan storage:link`
- Check file size limits in PHP configuration

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🤝 Support

For support, visit the `/support` page or contact the administrator.

---

**Built with ❤️ using Laravel**

# FinBank - Online Banking Platform

A full-stack internet banking application built with PHP, MySQL, and Tailwind CSS.

## Features

- User registration and authentication with internet banking ID
- Checking and savings account management
- Domestic, wire, and self transfers with PIN verification
- Transaction history and receipt generation
- KYC document upload and verification
- Card management (Visa, Mastercard, Amex)
- Support ticket system
- Loan and deposit tracking
- Admin dashboard with user management
- SMTP email notifications (welcome, login alerts, transaction updates)
- Dark mode support
- Rate limiting and CSRF protection
- Audit logging

## Tech Stack

- **Backend:** PHP 8.x (no framework, clean architecture)
- **Database:** MySQL / MariaDB
- **Frontend:** Tailwind CSS, vanilla JavaScript
- **Email:** PHPMailer via SMTP
- **Security:** bcrypt password hashing, CSRF tokens, input validation, rate limiting

## Setup

1. Clone the repository
2. Copy `.env.example` to `.env` and configure your database and SMTP credentials
3. Import `database/migrations/001_initial.sql` and `002_seed.sql` into your MySQL database
4. Install PHPMailer via Composer: `composer require phpmailer/phpmailer`
5. Point your web server document root to the project directory
6. Access the app at `/banking/`

## Project Structure

```
banking/
  accounts/        # user-facing account pages (dashboard, transfers, etc.)
  admin/           # admin panel (user management, transactions, settings)
  config/          # configuration files
  database/        # sql migrations and seeds
  pages/           # public pages (login, register, forgot password)
  public/          # static assets (css, js) and landing page
  src/
    Core/          # database, env, session, security utilities
    Helpers/       # formatting and validation helpers
    Models/        # data models (Account, Transaction, Card, etc.)
    Services/      # business logic (auth, transfers, email, kyc)
  uploads/         # user uploads (kyc documents, ticket attachments)
```

## Default Credentials

- **Admin:** admin@offshore.local / Admin1234
- **Test User:** Internet ID: 3000615625 / Test1234 (PIN: 1234)

## License

MIT

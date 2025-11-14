README - PHP LOGIN SYSTEM (no vendor)
---------------------------------------------------

Included files:
- admin_dashboard.php
analytics_placeholder.php
config.php
database.sql
edit_user.php
forgot_password.php
index.php
log_action.php
log_placeholder.php
manage_users.php
README.txt
register.php
reset_password.php
settings_placeholder.php
user_dashboard.php
verify.php

Important:
- This ZIP DOES NOT include the 'vendor' directory due to package size and
  environment differences. You must install Composer dependencies on your host.

How to install dependencies (recommended):
1) Install Composer: https://getcomposer.org/download/
2) Open Command Prompt in the project folder and run:
     composer require google/apiclient:^2.15

3) After successful install, ensure vendor/autoload.php exists.
4/ PHPMAILER FOLDER available or not
Quick test:
- Edit config.php and set DB_NAME and Google client values.
- Import databae.sql into MySQL (phpMyAdmin).
- Visit: http://localhost/php_login_system/index.php

If you cannot run Composer locally, alternative:
- Run Composer on another machine and copy the 'vendor' folder into this project.
- Or ask me and I can prepare a vendor-included ZIP (if allowed).

Note on PHP version:
- This package targets PHP 8.2+. You confirmed PHP 8.2.12.

Security:
- Never commit config.php with real secrets to public repos.
- Use HTTPS in production and ensure GOOGLE_REDIRECT_URI exactly matches the Google Console setting.




# PHP Login & Registration System

A secure and modern PHP Login & Registration System with email verification, password reset, and user dashboard. Built with PHP, MySQL, PHPMailer, and Bootstrap 5.

---

## 🔐 Features

- User Registration & Login
- Secure password hashing (`password_hash`, `password_verify`)
- Email verification (activation link)
- Forgot password & password reset via email
- Session-based authentication
- Logout functionality
- CSRF protection for important forms
- SQL Injection protection (prepared statements)
- XSS protection using `htmlspecialchars()`
- Bootstrap 5 responsive UI
- Clean and commented code

---

## 🧩 Technologies Used

- PHP 7.4+ / 8.x
- MySQL / MariaDB
- PHPMailer (via Composer)
- HTML5, CSS3, Bootstrap 5
- JavaScript (basic validation)

---

## ✅ Requirements

- Web server (Apache / Nginx / XAMPP / WAMP / Laragon / cPanel hosting)
- PHP 7.4 or higher
- MySQL / MariaDB
- PHP extensions:
  - mysqli
  - mbstring
  - json
  - openssl

---

## ⚙️ Installation

1. **Upload project**

   - Localhost (XAMPP):
     - Copy the folder to: `htdocs/your_project_name`
   - cPanel:
     - Upload all files to: `public_html/` or a subfolder.

2. **Create database**

   - Open **phpMyAdmin**
   - Click **New** → Enter a name (e.g. `login_system`)
   - Click **Create**

3. **Import SQL file**

   - Select your database
   - Go to **Import** tab
   - Choose: `database/login_system.sql`
   - Click **Import**

4. **Configure database (config.php)**

   Edit `config.php`:

   ```php
   $hostname = "localhost";
   $username = "root";
   $password = "";
   $database = "login_system";

Configure Base URL
__$base_url = "http://localhost/your_project_name/";
// For live:
// $base_url = "https://yourdomain.com/";

Configure SMTP (Email)

In config.php (or mail config section), set:

$mail->Host = "smtp.yourhost.com";
$mail->Username = "your-email@example.com";
$mail->Password = "your-email-password";
$mail->Port = 587; // or 465 for SSL
___________

------------------------------------------------------------
Test the system

Open: http://localhost/your_project_name/register.php

Try:

Register a new user

Check email verification

Login

Forgot password

Reset password

Access dashboard
----------------------------------------------------------
Folder Structure
.
             # CSS, JS, images
├── 
│   └── database.sql  # Database export
├── 
│   ├── header.php
│   └── footer.php
├── vendor/               # PHPMailer (Composer)
├── config.php            # DB + SMTP configuration
├── index.php             # Redirect / landing
├── register.php          # User registration
├── index.php             # User login
├── verify.php            # Email verification
├── forgot-password.php   # Request reset
├── reset-password.php    # Set new password
└── dashboard.php    # Protected user area
------admin_dashboard.php      # Protected admin area 
---------------------------------------------------------
Default Settings

No default user is created by default (depends on your DB file).

You can manually insert an admin/user from phpMyAdmin if needed.
------------------------------------------------------
Installation
---------------------------------
1. Upload the project files to your server (localhost or hosting).
2. Create a MySQL database using phpMyAdmin.
3. Import the provided SQL file (database/login_system.sql).
4. Open config.php and update:
   - Database host, username, password and name
   - Base URL of your website
   - SMTP settings for sending emails
5. Open the project URL in your browser and test:
   - Register, verify email, login, forgot password, reset password, dashboard.

---------------------------------
✔ Requirements
---------------------------------
- PHP 7.4 or higher
- MySQL / MariaDB
- Web server (Apache / Nginx / XAMPP / WAMP / cPanel)
- PHP extensions: mysqli, mbstring, json, openssl

--------------------------------------------------------
Quick: Create database & table (SQL)
Run this in phpMyAdmin or MySQL CLI:
CREATE DATABASE demo_crud;
USE demo_crud;
CREATE TABLE contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) DEFAULT 'uploads/default.png',
  token VARCHAR(255) DEFAULT NULL,
  token_expires DATETIME DEFAULT NULL,
  reset_token VARCHAR(255) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  is_verified TINYINT(1) DEFAULT 0,
  role ENUM('Admin','User') DEFAULT 'User',
  status ENUM('Active','Pending','Suspended') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
________________________________________
Configure PHPMailer (SMTP)
1.	Use config.php (example):
define('SMTP_HOST','smtp-relay.brevo.com');
define('SMTP_PORT',587);
define('SMTP_USER','your_brevo_username');
define('SMTP_PASS','your_brevo_password');
define('MAIL_FROM','no-reply@yourdomain.com');
define('MAIL_FROM_NAME','Your App');
2.	In your PHP code use PHPMailer and use statements (you already have that).
________________________________________
Test a simple PHP + MySQL script
Create test_conn.php in project folder:
<?php
require 'config.php';
if ($conn->ping()) echo "DB OK: " . $conn->host_info;
else echo "DB Error: " . $conn->connect_error;
Open in browser.
________________________________________
Troubleshooting common problems
•	Permission denied writing composer.json: Run terminal as admin or use correct folder permissions.
•	SSL/cURL errors downloading composer packages: Update system CA certs; on Windows use Git Bash or fix OpenSSL certificate store.
•	PHPMailer "Could not authenticate": check SMTP username/password, set correct encryption (STARTTLS on port 587), ensure SMTP provider allows SMTP (Google needs app passwords or OAuth).
•	Port conflicts: Change Apache port or stop conflicting service (IIS/Nginx).
•	vendor/autoload.php missing: run composer install or composer require phpmailer/phpmailer.
________________________________________
Security tips for production
•	Don’t run production with display_errors = On.
•	Use a real SMTP provider (Brevo/SendGrid) and secure credentials (don’t hardcode; use environment or config outside webroot).
•	Use HTTPS (Let’s Encrypt).
•	Set appropriate folder permissions, disable directory listing.
•	Use prepared statements (you already do) and password_hash / password_verify.


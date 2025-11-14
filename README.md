# php_login_system
he PHP Login System is a secure and modern user authentication application built using PHP, MySQL, and Bootstrap 5. It includes essential features such as user registration, login, email verification, password reset, and role-based access (Admin/User). The system follows best security practices .
![admin_dashboard](https://github.com/user-attachments/assets/546e6d6a-4be5-4607-a0cb-edfd72182f47)
![icon](https://github.com/user-attachments/assets/1b3907e5-163d-4609-ae85-a20fe60b1c23)
![login](https://github.com/user-attachments/assets/d3947a18-21e2-4a31-aa6d-d06386335457)
![login_i](https://github.com/user-attachments/assets/74443e3a-4f78-4aa8-abef-4af98da6afca)
![manage_users_imresizer](https://github.com/user-attachments/assets/c2f39d4e-5820-46ea-a905-b428ea87c5bc)
![register_imresizer](https://github.com/user-attachments/assets/8dcdcf2f-8084-4ebe-89fe-7a4a8c6eee9f)
![reset_password_imresizer (1)](https://github.com/user-attachments/assets/14f4d7e4-dd12-4d47-bdfe-e574050cd729)
![reset_password_imresizer (2)](https://github.com/user-attachments/assets/fed741cc-ec36-4a08-80dc-2ebd828c39a3)
![reset_password_imresizer](https://github.com/user-attachments/assets/aff9761d-7224-4108-8b94-03f5ed39e973)
![reset_password_sent_imresizer](https://github.com/user-attachments/assets/8457636f-937b-499d-93e2-c4a693f16f05)
![user_dashboard_imresizer](https://github.com/user-attachments/assets/dd3b7798-f0f4-4837-a4a1-5c435ea2cbe8)
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

ADDMIN LOGIN:- 
Go to create_admin.php file
Here:-
$adminEmail = "ADD YOUR EMAIL ID";
$adminPassword = "123456"; // you can change this if you want
$adminName = "Admin";
Then login




PHP 8 AND MYSQLI INSTALATION:-
Windows (recommended: XAMPP) — fastest, easiest
1.	Download XAMPP (Apache + PHP + MySQL + phpMyAdmin):
o	Go to https://www.apachefriends.org and download the latest XAMPP for Windows.
2.	Install:
o	Run the installer, accept defaults, install to C:\xampp.
3.	Start services:
o	Open XAMPP Control Panel → Start Apache and MySQL.
o	If ports conflict (80/443), stop IIS or change Apache ports in Config → Apache (httpd.conf).
4.	Test PHP:
o	Create C:\xampp\htdocs\test.php with:
o	<?php phpinfo();
o	Open http://localhost/test.php in browser.
5.	Access phpMyAdmin:
o	Visit http://localhost/phpmyadmin/ — default MySQL user: root with no password (set one).
6.	Configure PHP settings (optional):
o	Edit C:\xampp\php\php.ini (upload_max_filesize, post_max_size, display_errors in dev).
o	Restart Apache after changes.
7.	Install Composer:
o	Download Composer Windows installer from https://getcomposer.org and run it — point it to C:\xampp\php\php.exe.
o	Verify: open Command Prompt → composer -V.
8.	Create DB for your app:
o	phpMyAdmin → New → name e.g. crud_app_email_verify → run SQL to create contacts table (schema provided earlier).
9.	Common fixes:
o	If vendor/autoload.php missing → run composer require phpmailer/phpmailer in your project folder.
o	File permission: Windows usually OK; ensure uploads/ folder writable.
________________________________________
macOS — Homebrew (recommended) or MAMP
Homebrew method (cleaner)
1.	Install Homebrew (if not):
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
2.	Install PHP & MySQL:
3.	brew install php
4.	brew install mysql
5.	Start services:
6.	brew services start php
7.	brew services start mysql
8.	Secure MySQL & set root password:
9.	mysql_secure_installation
10.	Test PHP:
11.	php -v
12.	echo "<?php phpinfo();" > /tmp/test.php
13.	php -S localhost:8000 -t /tmp
14.	# Open http://localhost:8000/test.php
15.	Install Composer:
16.	curl -sS https://getcomposer.org/installer | php
17.	mv composer.phar /usr/local/bin/composer
18.	composer -V
19.	Optional: install phpMyAdmin via brew or use brew install phpmyadmin and configure.
20.	MAMP alternative: download MAMP app (contains Apache+PHP+MySQL), start servers via UI.
________________________________________
Ubuntu / Debian Linux (Apache + PHP + MySQL)
1.	Update & install packages:
2.	sudo apt update
3.	sudo apt install apache2 php libapache2-mod-php php-mysql mysql-server php-cli unzip
4.	Secure MySQL:
5.	sudo mysql_secure_installation
6.	Start/restart services:
7.	sudo systemctl enable --now apache2
8.	sudo systemctl enable --now mysql
9.	Test PHP:
o	Create /var/www/html/test.php:
o	<?php phpinfo();
o	Open http://localhost/test.php.
10.	Install Composer:
11.	php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
12.	php composer-setup.php
13.	sudo mv composer.phar /usr/local/bin/composer
14.	composer -V
15.	phpMyAdmin:
16.	sudo apt install phpmyadmin
or use MySQL CLI: mysql -u root -p
17.	Permissions: set www-data write permission for uploads:
18.	sudo mkdir -p /var/www/html/yourapp/uploads
19.	sudo chown -R www-data:www-data /var/www/html/yourapp/uploads
20.	sudo chmod -R 755 /var/www/html/yourapp/uploads
________________________________________
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


--------------------------------------------------------------------------------
ADDMIN LOGIN -
In craete.php file you can add
1- ADD YOUR EMAIL 
2- PASSWORD-123456

$adminEmail = "ADD YOUR EMAIL ID";
$adminPassword = "123456"; // you can change this if you want
$adminName = "Admin";

----------------------------------------------------------------------------------
INSTALATION STEPS:-

Install XAMPP or your preferred local server.

Start Apache and MySQL from the control panel.

Copy the project folder to htdocs (for XAMPP).

Open phpMyAdmin → create a new database (e.g., login_system).

Import the provided SQL file (users_table.sql).

Run composer install to install PHPMailer.

Update config.php with your database and email credentials.

Open your browser and visit:

http://localhost/php-login-system/
--------------------------------------------------------------------------------------------------

Software Requirements:- 

XAMPP / WAMP / LAMP / MAMP (any local server stack)

Must include Apache and MySQL

PHP version: 8.0 or higher (recommended: PHP 8.1+)

MySQL version: 5.7 or higher

Composer – for installing PHPMailer and dependencies

Modern web browser (Chrome, Edge, Firefox, Safari, etc.)

Code editor (VS Code, Sublime Text, PHPStorm, etc.)
--------------------------------------------------------------------------------------------------------
Tools & Technologies Used:- 

PHP 8+ — Core backend scripting

MySQL — Database management

PHPMailer — Sending secure verification/reset emails

Bootstrap 5 — Responsive front-end layout

HTML5, CSS3, JavaScript — UI and interactivity

Composer — Dependency management
------------------------------------------------------------------------------------------------------------














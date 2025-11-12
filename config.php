
<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'demo_crud';


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die('Database connection failed: ' . $conn->connect_error); }

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
    ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$BASE_URL = $protocol . $host . $path;
// SMTP Config
define('SMTP_HOST', '$HOST_NAME');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '$USER_NAME');
define('SMTP_PASSWORD', '$PASSWORD');
define('MAIL_FROM', '$EMAIL');
define('MAIL_FROM_NAME', 'CRUD App');
?>

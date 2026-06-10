# MySQL Setup Guide for Purple Guess

## Prerequisites
You need MySQL server installed and running on your system.

### Windows Installation
- Download MySQL from: https://dev.mysql.com/downloads/mysql/
- Or use: `choco install mysql` (if using Chocolatey)
- Or use: Docker container with MySQL image

### macOS Installation
- `brew install mysql`
- Start service: `brew services start mysql`

### Linux Installation
- Ubuntu/Debian: `sudo apt-get install mysql-server`
- Start service: `sudo systemctl start mysql`

## Configuration Steps

### 1. Copy Configuration File
```bash
cp .env.example .env
```

### 2. Edit `.env` with Your MySQL Credentials
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=purple_game
DB_USER=root
DB_PASS=your_password_here
```

### 3. Load Environment Variables in PHP

Add this to the top of your PHP entry point or include a bootstrap file:

```php
<?php
// Load .env file
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Now include your files
require_once 'db.php';
```

### 4. Database Auto-Creation

The `db.php` file will automatically:
- Connect to MySQL server
- Create the `purple_game` database if it doesn't exist
- Create all required tables with proper indexes
- Insert default admin configuration

### 5. Verify Connection

Test with a simple PHP script:
```php
<?php
require_once 'db.php';
$db = Database::getInstance()->getPDO();
$result = $db->query('SELECT 1');
echo $result->fetch() ? "✓ MySQL connection successful!" : "✗ Connection failed";
?>
```

## Remote MySQL Server

To use a remote MySQL server:
1. Update `.env` with remote host, username, and password
2. Ensure remote server allows connections from your IP
3. Database creation requires root or admin privileges on remote server

## Troubleshooting

### Connection Refused
- Verify MySQL service is running
- Check host/port/credentials
- Default MySQL port is 3306

### Access Denied
- Verify username and password
- MySQL user may need `CREATE DATABASE` privilege

### Charset Issues
- The database is set to `utf8mb4_unicode_ci` by default
- Supports full UTF-8 including emoji and special characters

## API Endpoints Compatibility

All existing API endpoints work seamlessly with MySQL:
- Authentication endpoints (login, register, logout)
- Room management endpoints
- Game state polling
- Admin configuration

No code changes needed - just update the database configuration!

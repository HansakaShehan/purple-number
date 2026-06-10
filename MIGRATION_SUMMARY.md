# MySQL Migration Summary

## What Changed

### 1. Database Layer (`db.php`)
- **Before**: SQLite file-based database at `purple_game.db`
- **After**: MySQL server connection with auto-database creation
- Features:
  - Reads credentials from environment variables (`.env` file)
  - Automatically creates `purple_game` database
  - Creates all 4 tables with proper indexes
  - Supports both localhost and remote MySQL servers

### 2. Configuration System (`config.php`) - NEW
- Centralized bootstrap file for all PHP endpoints
- Loads `.env` environment variables
- Initializes database connection
- Manages sessions with security headers
- Sets up error handling and CORS headers

### 3. Environment Configuration (`.env`)
- Contains MySQL connection credentials
- Supports customization without code changes
- Template provided: `.env.example`

### 4. API Endpoints
All 10 endpoints updated to use `config.php`:
- `api/auth/login.php`
- `api/auth/register.php`
- `api/auth/logout.php`
- `api/auth/status.php`
- `api/rooms/create.php`
- `api/rooms/join.php`
- `api/rooms/list.php`
- `api/game/state.php`
- `api/game/guess.php`
- `api/admin/config.php`

## MySQL Connection Details

### Default Configuration
```
Host: localhost
Port: 3306
Database: purple_game (auto-created)
Username: root
Password: (empty)
```

### Connection String (DSN)
```
mysql:host=localhost;port=3306;dbname=purple_game;charset=utf8mb4
```

## Database Schema

### Tables
1. **users** - User accounts with authentication
2. **admin_config** - Game settings (duration, turn time)
3. **game_sessions** - Multiplayer game room tracking
4. **guesses** - Player guesses with timestamps

All tables have:
- InnoDB engine (supports transactions)
- UTF-8 charset support
- Proper indexes for performance
- Foreign key constraints with cascade delete
- Timestamps for audit trails

## Migration Path

### Quick Start
1. Copy `.env.example` to `.env`
2. Update MySQL credentials in `.env`
3. Run your PHP server
4. Database creates automatically on first connection

### No Code Changes Needed
- All API endpoints work unchanged
- Frontend code remains identical
- Game logic unchanged
- Only database backend switched from SQLite to MySQL

## Performance Improvements (MySQL vs SQLite)

| Feature | SQLite | MySQL |
|---------|--------|-------|
| Concurrent users | Single-threaded | Multi-threaded |
| Large datasets | ~1MB limit | Unlimited |
| Indexes | Basic | Advanced |
| Transactions | Limited | Full ACID |
| Remote access | File-based only | Network access |
| Scaling | Difficult | Easy |

## Files Modified/Created

```
✓ db.php - MySQL connection logic
✓ config.php - NEW: Bootstrap configuration
✓ .env.example - NEW: Environment template
✓ MYSQL_SETUP.md - NEW: Setup guide
✓ All API endpoints - Updated to use config.php
✓ guess.php - Updated to use config.php
```

## Next Steps

1. **Install MySQL**: Download from mysql.com or use Docker
2. **Configure `.env`**: Set your MySQL credentials
3. **Test Connection**: Visit `/api/auth/status.php` in browser
4. **Register Account**: Create first user account
5. **Play Game**: Start multiplayer matches

## Troubleshooting

### MySQL Not Running
```bash
# Windows: Start MySQL service
net start MySQL80

# macOS: Start with Homebrew
brew services start mysql

# Linux: Start with systemd
sudo systemctl start mysql
```

### Connection Error
- Check MySQL credentials in `.env`
- Verify MySQL is listening on configured port
- Ensure database user has `CREATE DATABASE` privilege

### Charset Issues
- All tables use `utf8mb4_unicode_ci`
- Supports emoji and international characters

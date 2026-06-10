# Purple Guess Game - MySQL Quick Setup

## ⚡ 5-Minute Setup

### Step 1: Prepare MySQL Credentials
```bash
# Copy example config
cp .env.example .env

# Edit .env with your MySQL details
# DB_HOST=localhost
# DB_USER=root
# DB_PASS=your_password
```

### Step 2: Database Auto-Creates
When you access the app, `db.php` automatically:
- Connects to MySQL server
- Creates `purple_game` database
- Creates all 4 tables with indexes
- Inserts default configuration

**No manual SQL needed!** ✓

### Step 3: Start Playing
```
http://localhost/purple-php/index.php
```

## 📁 Project Structure

```
purple-php/
├── config.php ..................... App bootstrap (loads .env)
├── db.php ......................... MySQL connection
├── index.php ...................... SPA entry point
├── guess.php ...................... Legacy endpoint
├── .env.example ................... Config template
│
├── api/
│   ├── auth/
│   │   ├── login.php .............. User login
│   │   ├── register.php ........... User signup
│   │   ├── logout.php ............. Session destroy
│   │   └── status.php ............. Check auth status
│   │
│   ├── rooms/
│   │   ├── create.php ............. Create game room
│   │   ├── join.php ............... Join existing room
│   │   └── list.php ............... List available rooms
│   │
│   ├── game/
│   │   ├── state.php .............. Poll game state
│   │   └── guess.php .............. Submit guess
│   │
│   └── admin/
│       └── config.php ............. Admin settings
│
├── assets/
│   ├── app.js ..................... Core managers
│   ├── style.css .................. SPA styling
│   └── screens/
│       ├── router.js .............. SPA routing
│       ├── auth.js ................ Login/register
│       ├── lobby.js ............... Room selection
│       ├── game.js ................ Game board
│       └── results.js ............. Winner display
```

## 🎮 Architecture

### Frontend (SPA)
- 4 Screens: Login → Lobby → Game → Results
- Client-side routing (no page reloads)
- Real-time polling (1s intervals)
- Web Audio API for background music + effects

### Backend (API)
- 10 RESTful endpoints
- Session-based authentication
- MySQL database
- Turn-based multiplayer logic

### Database (MySQL)
- 4 Tables: users, admin_config, game_sessions, guesses
- InnoDB engine with transactions
- UTF-8mb4 charset support
- Indexed for performance

## 🔗 API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/auth/login.php` | User login |
| POST | `/api/auth/register.php` | User signup |
| POST | `/api/auth/logout.php` | Logout |
| GET | `/api/auth/status.php` | Check auth |
| POST | `/api/rooms/create.php` | Create room |
| POST | `/api/rooms/join.php` | Join room |
| GET | `/api/rooms/list.php` | List rooms |
| GET | `/api/game/state.php` | Poll game state |
| POST | `/api/game/guess.php` | Submit guess |
| GET/POST | `/api/admin/config.php` | Admin settings |

## 🛠️ Configuration

### Environment Variables (.env)
```
DB_HOST=localhost      # MySQL server host
DB_PORT=3306          # MySQL server port
DB_NAME=purple_game   # Database name
DB_USER=root          # MySQL username
DB_PASS=              # MySQL password
```

### Admin User Setup
To make a user admin:
```sql
UPDATE users SET is_admin = 1 WHERE username = 'admin_user';
```

Then the admin can set game duration (60-600 seconds) in the lobby panel.

## 🚀 Features

✅ **User Accounts** - Register and login with password hashing
✅ **Multiplayer** - 2-player turn-based battles
✅ **Room Codes** - 4-character room codes for easy sharing
✅ **Real-time Sync** - Polling updates scores every 1 second
✅ **Audio System** - Background music and sound effects
✅ **Purple Theme** - Gradient UI with animations
✅ **Admin Panel** - Configure game duration
✅ **Responsive** - Mobile and desktop support

## 📝 Game Flow

1. **Login/Register** - Create account or login
2. **Lobby** - Create room or join with code
3. **Waiting** - Wait for opponent to join
4. **Game** - 5 minutes of guessing battles
   - Alternate 10-second turns
   - Guess numbers 1-10
   - Try to match the secret number
5. **Results** - See winner and stats

## 🐛 Troubleshooting

### Connection Error
```
"Database connection failed"
```
→ Check MySQL is running and credentials in `.env` are correct

### Tables Not Created
→ Verify MySQL user has `CREATE DATABASE` privilege

### Session Issues
→ Clear browser cookies and try again

### Polling Not Working
→ Check browser console for CORS errors

## 📊 Performance

- Database: MySQL with indexed queries
- Polling: 1-second intervals (adjustable)
- Sessions: Server-side with file/database storage
- Responses: JSON (< 1KB per request)

## 🔐 Security Notes

- Passwords: bcrypt hashing (PASSWORD_BCRYPT)
- Sessions: HTTPOnly cookies
- CORS: Set to allow origins as needed
- HTTPS: Recommended for production
- Admin: Separate privilege level

## 📞 Support

See documentation:
- `MYSQL_SETUP.md` - Detailed MySQL setup
- `MIGRATION_SUMMARY.md` - SQLite → MySQL migration
- Inline comments in config.php, db.php, and endpoints

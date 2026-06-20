// Translation System - English Only

const translations = {
  en: {
    'app.title': 'Purple Guess',
    'app.subtitle': 'Multiplayer Number Guessing Game',
    'login.tab': 'Login',
    'register.tab': 'Register',
    'login.username': 'Username',
    'login.password': 'Password',
    'login.button': 'Login',
    'login.error': 'Login failed',
    'register.username': 'Username (3+ chars)',
    'register.password': 'Password (6+ chars)',
    'register.button': 'Register',
    'register.error': 'Registration failed',
    'footer.credit': 'Made with ❤️ by HanSakaSheHan',
    'lobby.create': 'Create Room',
    'lobby.createButton': 'Create New Room',
    'lobby.roomCode': 'Room Code:',
    'lobby.copy': 'Copy Code',
    'lobby.waiting': 'Waiting for opponent to join...',
    'lobby.join': 'Join Room',
    'lobby.joinInput': 'Enter room code',
    'lobby.joinButton': 'Join Room',
    'lobby.joinError': 'Failed to join room',
    'lobby.admin': '⚙️ Admin Settings',
    'lobby.rounds': 'Rounds (5-100)',
    'lobby.gemCategories': 'Gem Categories',
    'lobby.gemCategoriesHint': 'Uncheck a category to disable it in all games.',
    'lobby.save': 'Save Settings',
    'lobby.leaderboard': '🏆 Leaderboard',
    'lobby.logout': 'Logout',
    'game.title': 'Purple Guess — Battle',
    'game.myGems': '💎 My Gems:',
    'game.player': 'Player',
    'game.correct': 'Correct:',
    'game.misses': 'Misses:',
    'game.gems': '💎 Gems:',
    'game.vs': 'vs',
    'game.waiting': 'Waiting...',
    'game.turnTime': 'Turn Time:',
    'game.audio': '🔊 Sound',
    'game.guess': 'Submit Guess',
    'game.selected': 'Selected:',
    'game.watching': "Opponent's Turn — Waiting...",
    'game.history': 'Guess History',
    'game.round': 'Round',
    'game.guess_col': 'Guess',
    'game.real': 'Real Number',
    'game.result': 'Result',
    'game.correct_result': '✓ Correct',
    'game.wrong_result': '✗ Wrong',
    'game.correct_text': '✓ Correct!',
    'game.wrong_text': '✗ Wrong Number',
    'game.status': 'Status',
    'game.ready': 'Ready',
    'game.round_label': 'Round',
    'results.title': 'Game Over!',
    'results.winner': '🏆',
    'results.draw': 'Draw!',
    'results.nextGame': 'Play Again',
    'results.lobby': 'Back to Lobby',
    'leaderboard.title': 'Leaderboard',
    'leaderboard.rank': 'Rank',
    'leaderboard.player': 'Player',
    'leaderboard.games': 'Games',
    'leaderboard.wins': 'Wins',
    'leaderboard.winRate': 'Win Rate',
    'leaderboard.accuracy': 'Accuracy',
    'leaderboard.gems': 'Gems',
    'leaderboard.you': 'You',
    'sound.title': '🔊 Game Sound',
    'sound.question': 'Enable sound effects and background music?',
    'sound.enable': '✓ Enable Sound',
    'sound.disable': '✗ Disable Sound',
    'sound.hint': 'You can change this anytime during gameplay',
    'language.select': '🌐 Language',
    'language.english': 'English',
    'language.sinhala': 'සිංහල',
    'common.home': 'Home',
  }
};

class TranslationManager {
  constructor() {
    this.currentLanguage = 'en';
  }

  setLanguage(lang) {
    // Always use English, ignore language switching
    return true;
  }

  getLanguage() {
    return 'en';
  }

  t(key) {
    const text = translations['en']?.[key] || key;
    return text;
  }

  updatePageText() {
    // Update all elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      el.textContent = this.t(key);
    });

    // Update all placeholders with data-i18n-placeholder attribute
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      el.placeholder = this.t(key);
    });

    // Dispatch event for custom translation updates
    window.dispatchEvent(new CustomEvent('language-changed', { detail: { language: 'en' } }));
  }
}

// Global instance
window.translationManager = new TranslationManager();

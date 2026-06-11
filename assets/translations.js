// Translation System - English & Sinhala
const translations = {
  en: {
    // App Title
    'app.title': 'Purple Guess',
    'app.subtitle': 'Multiplayer number guessing battle',
    
    // Login Screen
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
    'footer.credit': 'Developed by HanSakaSheHan ❤️',
    
    // Lobby Screen
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
    'lobby.rounds': 'Total Rounds (5-100)',
    'lobby.save': 'Save Settings',
    'lobby.leaderboard': '🏆 View Leaderboard',
    'lobby.logout': 'Logout',
    
    // Game Screen
    'game.title': 'Purple Guess — Battle',
    'game.myGems': '💎 My Gems:',
    'game.player': 'Player',
    'game.correct': 'Correct:',
    'game.misses': 'Misses:',
    'game.gems': '💎 Gems:',
    'game.vs': 'VS',
    'game.waiting': 'Waiting...',
    'game.turnTime': 'Turn Time:',
    'game.audio': '🔊 Audio',
    'game.guess': 'Send Guess',
    'game.selected': 'Selected:',
    'game.watching': "Opponent's turn — Watching...",
    'game.history': 'Round History',
    'game.round': 'Round',
    'game.guess_col': 'Guess',
    'game.real': 'Real #',
    'game.result': 'Result',
    'game.correct_result': '✓ Correct',
    'game.wrong_result': '✗ Wrong',
    'game.correct_text': '✓ Correct!',
    'game.wrong_text': '✗ Wrong number',
    'game.status': 'Status',
    'game.ready': 'Ready',
    'game.round_label': 'Round',
    
    // Results Screen
    'results.title': 'Game Over!',
    'results.winner': '🏆',
    'results.draw': 'Draw!',
    'results.nextGame': 'Play Again',
    'results.lobby': 'Back to Lobby',
    
    // Leaderboard
    'leaderboard.title': 'Leaderboard',
    'leaderboard.rank': 'Rank',
    'leaderboard.player': 'Player',
    'leaderboard.games': 'Games',
    'leaderboard.wins': 'Wins',
    'leaderboard.winRate': 'Win Rate',
    'leaderboard.accuracy': 'Accuracy',
    'leaderboard.gems': 'Gems',
    'leaderboard.you': 'YOU',
    
    // Sound Preference
    'sound.title': '🔊 Game Sounds',
    'sound.question': 'Would you like to enable sound effects and background music?',
    'sound.enable': '✓ Enable Sounds',
    'sound.disable': '✗ Disable Sounds',
    'sound.hint': 'You can change this anytime during gameplay',
    
    // Language
    'language.select': '🌐 Language',
    'language.english': 'English',
    'language.sinhala': 'සිංහල',
    
    // Common
    'common.home': 'Home',
  },
  
  si: {
    // App Title
    'app.title': 'පර්පල ගෙස්',
    'app.subtitle': 'බහුකාර්ය අංක অনুමාන සටන',
    
    // Login Screen
    'login.tab': 'ඉතුරුවන්න',
    'register.tab': 'ලියාපදිංචි වන්න',
    'login.username': 'පරිශීලක නාමය',
    'login.password': 'ගිණුම් අංකය',
    'login.button': 'ඉතුරුවන්න',
    'login.error': 'ඉතුරුවීම අසාර්థක විය',
    'register.username': 'පරිශීලක නාමය (අක්ෂර 3+)',
    'register.password': 'ගිණුම් අංකය (අක්ෂර 6+)',
    'register.button': 'ලියාපදිංචි වන්න',
    'register.error': 'ලියාපදිංචිකරණය අසාර්థක විය',
    'footer.credit': 'HanSakaSheHan විසින් සකස් කරන ලදී ❤️',
    
    // Lobby Screen
    'lobby.create': 'කාමරය සෑදුණු',
    'lobby.createButton': 'නව කාමරය සෑදුණු',
    'lobby.roomCode': 'කාමර කෝඩ්:',
    'lobby.copy': 'කෝඩ් පිටපතක්',
    'lobby.waiting': 'විරුද්ධවාදියා එක්වීමට බලා ගැනේ...',
    'lobby.join': 'කාමරයට එක්වෙන්න',
    'lobby.joinInput': 'කාමර කෝඩ් ඇතුළු කරන්න',
    'lobby.joinButton': 'කාමරයට එක්වෙන්න',
    'lobby.joinError': 'කාමරයට එක්වීම අසාර්థක විය',
    'lobby.admin': '⚙️ පරිපාලක සැකසුම්',
    'lobby.rounds': 'සම්පූර්ණ ගෙස් (5-100)',
    'lobby.save': 'සැකසුම් සුරකින්න',
    'lobby.leaderboard': '🏆 ශ්‍රේණිගත ලැයිස්තුව',
    'lobby.logout': 'ඉතුරුවීම ශেෂ කරන්න',
    
    // Game Screen
    'game.title': 'පර්පල ගෙස් — සටන',
    'game.myGems': '💎 මගේ බිතුපත්:',
    'game.player': 'ක්‍රීඩකයා',
    'game.correct': 'නිවැරදි:',
    'game.misses': 'මිස්:',
    'game.gems': '💎 බිතුපත්:',
    'game.vs': 'එදිරිව',
    'game.waiting': 'බලා ගැනේ...',
    'game.turnTime': 'ටර්න් කාලය:',
    'game.audio': '🔊 ශබ්ද',
    'game.guess': 'අනුමාන යැවිය',
    'game.selected': 'තෝරා ගැනුණු:',
    'game.watching': "විරුද්ධවාදියාගේ පැය — බලා ගැනේ...",
    'game.history': 'ගෙස් ඉතිහාසය',
    'game.round': 'ගෙස්',
    'game.guess_col': 'අනුමාන',
    'game.real': 'සත්‍ය අංක',
    'game.result': 'ප්‍රතිඵලය',
    'game.correct_result': '✓ නිවැරදි',
    'game.wrong_result': '✗ වැරදි',
    'game.correct_text': '✓ නිවැරදි!',
    'game.wrong_text': '✗ වැරදි අංකය',
    'game.status': 'තත්ත්වය',
    'game.ready': 'සූදානම්',
    'game.round_label': 'ගෙස්',
    
    // Results Screen
    'results.title': 'ක්‍රීඩාව අවසන්!',
    'results.winner': '🏆',
    'results.draw': 'සමාන!',
    'results.nextGame': 'නැවත ක්‍රීඩා කරන්න',
    'results.lobby': 'ලොබියට ආපසු',
    
    // Leaderboard
    'leaderboard.title': 'ශ්‍රේණිගත ලැයිස්තුව',
    'leaderboard.rank': 'ශ්‍රේණිය',
    'leaderboard.player': 'ක්‍රීඩකයා',
    'leaderboard.games': 'ක්‍රීඩා',
    'leaderboard.wins': 'ජයග්‍රහණ',
    'leaderboard.winRate': 'ජයග්‍රහණ අනුපාතය',
    'leaderboard.accuracy': 'නිරවද්‍යතාවය',
    'leaderboard.gems': 'බිතුපත්',
    'leaderboard.you': 'ඔබ',
    
    // Sound Preference
    'sound.title': '🔊 ක්‍රීඩා ශබ්දය',
    'sound.question': 'ශබ්ද প්‍රතිපල සහ පසුතල සङ්গීතය සක්‍රිය කිරීමට ඔබට අවශ්‍යද?',
    'sound.enable': '✓ ශබ්දය සක්‍රිය කරන්න',
    'sound.disable': '✗ ශබ්දය අක්‍රිය කරන්න',
    'sound.hint': 'ක්‍රීඩා කිරීමේදී ඕනෑම කාලයක එය වෙනස් කළ හැකිය',
    
    // Language
    'language.select': '🌐 භාෂාව',
    'language.english': 'English',
    'language.sinhala': 'සිංහල',
    
    // Common
    'common.home': 'ගෙදර',
  }
};

// Translation Manager
class TranslationManager {
  constructor() {
    this.currentLanguage = localStorage.getItem('purple-guess-language') || 'en';
  }

  setLanguage(lang) {
    if (translations[lang]) {
      this.currentLanguage = lang;
      localStorage.setItem('purple-guess-language', lang);
      this.updatePageText();
      return true;
    }
    return false;
  }

  getLanguage() {
    return this.currentLanguage;
  }

  t(key) {
    const text = translations[this.currentLanguage]?.[key] || 
                 translations['en'][key] || 
                 key;
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
    window.dispatchEvent(new CustomEvent('language-changed', { detail: { language: this.currentLanguage } }));
  }
}

// Global instance
window.translationManager = new TranslationManager();

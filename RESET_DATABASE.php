<?php
/**
 * Reset database for testing
 * Drops and recreates all tables to clear old data
 */

require_once 'db.php';

$db = Database::getInstance()->getPDO();

echo "⚠️  RESETTING DATABASE...\n";

try {
    // Drop tables in correct order (foreign key dependencies)
    $tables = ['guesses', 'game_sessions', 'admin_config', 'users'];
    
    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS `$table`");
        echo "✓ Dropped table: $table\n";
    }
    
    echo "\n✓ Database reset complete!\n";
    echo "   Tables will be automatically recreated on next request.\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

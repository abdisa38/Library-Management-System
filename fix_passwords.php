<?php
/**
 * Fix Default User Passwords
 * Run this script once to set correct passwords
 */

require_once 'config/database.php';

try {
    $db = getDB();
    
    // Hash passwords
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $librarianPassword = password_hash('librarian123', PASSWORD_DEFAULT);
    $studentPassword = password_hash('student123', PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$adminPassword]);
    echo "✓ Admin password updated\n";
    
    // Update librarian password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'librarian'");
    $stmt->execute([$librarianPassword]);
    echo "✓ Librarian password updated\n";
    
    // Update student password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'student'");
    $stmt->execute([$studentPassword]);
    echo "✓ Student password updated\n";
    
    echo "\n✅ All passwords have been updated successfully!\n\n";
    echo "You can now login with:\n";
    echo "Admin: admin / admin123\n";
    echo "Librarian: librarian / librarian123\n";
    echo "Student: student / student123\n\n";
    echo "⚠️ Delete this file (fix_passwords.php) after running it for security.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

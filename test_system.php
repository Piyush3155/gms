<?php
// Test script for Gym Management System
echo "🏋️ Gym Management System - Testing Suite\n";
echo "==========================================\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    require_once 'includes/db.php';
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Configuration Loading
echo "\n2. Testing Configuration Loading...\n";
try {
    require_once 'includes/config.php';
    echo "✅ Configuration loaded successfully\n";
    echo "   - Site URL: " . SITE_URL . "\n";
    echo "   - Site Name: " . SITE_NAME . "\n";
} catch (Exception $e) {
    echo "❌ Configuration loading failed: " . $e->getMessage() . "\n";
}

// Test 3: Database Tables Check
echo "\n3. Testing Database Tables...\n";
$required_tables = [
    'users', 'members', 'trainers', 'plans', 'attendance',
    'payments', 'workout_plans', 'diet_plans', 'expenses',
    'settings', 'equipment', 'member_progress', 'group_classes', 'class_bookings'
];

$existing_tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $existing_tables[] = $row[0];
}

$missing_tables = array_diff($required_tables, $existing_tables);
$extra_tables = array_diff($existing_tables, $required_tables);

if (empty($missing_tables)) {
    echo "✅ All required tables exist\n";
} else {
    echo "❌ Missing tables: " . implode(', ', $missing_tables) . "\n";
}

if (!empty($extra_tables)) {
    echo "ℹ️  Extra tables found: " . implode(', ', $extra_tables) . "\n";
}

// Test 4: Sample Data Check
echo "\n4. Testing Sample Data...\n";
$tests = [
    ['users', 'Admin user exists', "SELECT COUNT(*) as count FROM users WHERE email = 'admin@gym.com'"],
    ['members', 'Sample members exist', "SELECT COUNT(*) as count FROM members"],
    ['trainers', 'Sample trainers exist', "SELECT COUNT(*) as count FROM trainers"],
    ['plans', 'Membership plans exist', "SELECT COUNT(*) as count FROM plans"],
    ['equipment', 'Equipment data exists', "SELECT COUNT(*) as count FROM equipment"],
    ['group_classes', 'Group classes exist', "SELECT COUNT(*) as count FROM group_classes"],
    ['member_progress', 'Progress data exists', "SELECT COUNT(*) as count FROM member_progress"]
];

foreach ($tests as $test) {
    $result = $conn->query($test[2]);
    $count = $result->fetch_assoc()['count'];
    if ($count > 0) {
        echo "✅ {$test[1]} ({$count} records)\n";
    } else {
        echo "❌ {$test[1]}\n";
    }
}

// Test 5: File Structure Check
echo "\n5. Testing File Structure...\n";
$required_files = [
    'index.php',
    'login.php',
    'dashboard.php',
    'includes/config.php',
    'includes/db.php',
    'includes/header.php',
    'admin/members.php',
    'admin/equipment.php',
    'admin/member_progress.php',
    'admin/group_classes.php',
    'admin/notifications.php',
    'member/classes.php',
    'assets/css/style.css'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} exists\n";
    } else {
        echo "❌ {$file} missing\n";
    }
}

// Test 6: PHP Syntax Check for Key Files
echo "\n6. Testing PHP Syntax...\n";
$syntax_files = [
    'includes/config.php',
    'admin/equipment.php',
    'admin/member_progress.php',
    'admin/group_classes.php',
    'member/classes.php'
];

foreach ($syntax_files as $file) {
    $output = shell_exec("php -l {$file} 2>&1");
    if (strpos($output, 'No syntax errors detected') !== false) {
        echo "✅ {$file} syntax OK\n";
    } else {
        echo "❌ {$file} syntax error: {$output}\n";
    }
}

// Test 7: Authentication Functions
echo "\n7. Testing Authentication Functions...\n";
if (function_exists('sanitize')) {
    echo "✅ sanitize() function exists\n";
} else {
    echo "❌ sanitize() function missing\n";
}

if (function_exists('is_logged_in')) {
    echo "✅ is_logged_in() function exists\n";
} else {
    echo "❌ is_logged_in() function missing\n";
}

if (function_exists('get_user_role')) {
    echo "✅ get_user_role() function exists\n";
} else {
    echo "❌ get_user_role() function missing\n";
}

// Test 8: Gym Settings
echo "\n8. Testing Gym Settings...\n";
$settings = get_gym_settings();
if (!empty($settings['gym_name'])) {
    echo "✅ Gym settings loaded: {$settings['gym_name']}\n";
} else {
    echo "❌ Gym settings not configured\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "🏁 Testing Complete!\n";
echo "\n📋 Next Steps:\n";
echo "1. Import the database schema: database/schema.sql\n";
echo "2. Access the application at: http://localhost/gms/\n";
echo "3. Login with: admin@gym.com / password\n";
echo "4. Test all new features: Equipment, Progress Tracking, Classes\n";

$conn->close();
?>
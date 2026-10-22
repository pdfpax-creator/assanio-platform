<?php
// CLEANUP: Delete all dummy/test data - upload to app/, run once, DELETE after
// WARNING: This deletes test stores, test orders, test users, test leads!
require_once __DIR__ . '/lib.php';

$pdo = db();

echo "<h2>🧹 Cleaning dummy data...</h2><pre>";

// 1. Delete test stores (keep Downtown Slice + BR 9 Cafe)
$keepSlugs = ['downtown-slice', 'br9-cafe'];
$stores = $pdo->query("SELECT id, name, slug FROM stores")->fetchAll(PDO::FETCH_ASSOC);
$deletedStores = 0;
foreach ($stores as $s) {
    if (!in_array($s['slug'], $keepSlugs)) {
        // Delete store files
        $dir = __DIR__ . '/stores/' . $s['slug'];
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
            rmdir($dir);
        }
        // Delete from DB (orders cascade)
        $pdo->prepare("DELETE FROM stores WHERE id=?")->execute([$s['id']]);
        echo "🗑️ Deleted store: {$s['name']} ({$s['slug']})\n";
        $deletedStores++;
    }
}
echo "Stores deleted: $deletedStores\n\n";

// 2. Delete test orders (keep only real ones - for now delete all test)
// Orders for deleted stores are already gone via cascade
// Delete remaining test orders by customer name pattern
$testOrders = $pdo->query("SELECT id, customer_name FROM orders WHERE customer_name LIKE '%Test%' OR customer_name LIKE '%test%' OR customer_name = 'Beta Tester'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($testOrders as $o) {
    $pdo->prepare("DELETE FROM orders WHERE id=?")->execute([$o['id']]);
    echo "🗑️ Deleted order: {$o['id']} ({$o['customer_name']})\n";
}
echo "Test orders deleted: " . count($testOrders) . "\n\n";

// 3. Delete test users (keep super admin + real callers)
$keepEmails = ['mrmujtabaawan@gmail.com', 'mrmujtabawan@gmail.com']; // super + real caller
$users = $pdo->query("SELECT id, name, email, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    if (!in_array($u['email'], $keepEmails)) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$u['id']]);
        echo "🗑️ Deleted user: {$u['name']} ({$u['email']}, {$u['role']})\n";
    }
}
echo "\n";

// 4. Delete test leads
$testLeads = $pdo->query("SELECT id, name FROM leads WHERE name LIKE '%Test%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($testLeads as $l) {
    $pdo->prepare("DELETE FROM leads WHERE id=?")->execute([$l['id']]);
    echo "🗑️ Deleted lead: {$l['name']}\n";
}
echo "Test leads deleted: " . count($testLeads) . "\n\n";

// 5. Delete test commissions/referrals
try {
    $pdo->exec("DELETE FROM commissions WHERE amount < 5000"); // test amounts were 1800
    echo "🗑️ Test commissions cleared\n";
} catch (Exception $e) { echo "No commissions table or error: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("DELETE FROM referrals WHERE referrer_email LIKE '%test%'");
    echo "🗑️ Test referrals cleared\n";
} catch (Exception $e) { echo "Referral cleanup: " . $e->getMessage() . "\n"; }

echo "\n✅ CLEANUP COMPLETE!\n";
echo "Kept: downtown-slice, br9-cafe, super admin, real caller\n";
echo "Delete this file now.\n";
echo "</pre>";

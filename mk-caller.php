<?php
// Create caller + assign leads. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

// 1. Create caller user
$callerName = 'Ali Caller';
$callerEmail = 'caller1@assanio.pk';
$callerPass = 'caller123';

$st = $pdo->prepare("SELECT id FROM users WHERE email=?");
$st->execute([$callerEmail]);
if ($st->fetch()) {
    echo "Caller already exists\n";
} else {
    $hash = password_hash($callerPass, PASSWORD_BCRYPT);
    $refCode = 'CALL-' . strtoupper(substr(md5($callerEmail), 0, 4));
    $st = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, approved, referral_code, created_at) VALUES (?,?,?,?,?, ?,datetime('now'))");
    $st->execute([$callerName, $callerEmail, $hash, 'caller', 1, $refCode]);
    $callerId = $pdo->lastInsertId();
    echo "Caller created: $callerName (ID: $callerId, Code: $refCode)\n";
}

// Get caller ID
$st = $pdo->prepare("SELECT id, referral_code FROM users WHERE email=?");
$st->execute([$callerEmail]);
$caller = $st->fetch(PDO::FETCH_ASSOC);
$callerId = $caller['id'];

// 2. Assign unassigned leads (all except TKR 4 which is assigned to Mujtaba)
$leads = [
    ['Wildmen Restaurant', '03008552989', 'Bahria Town Phase 4'],
    ['Rumba Restaurant', '03070786220', 'Bahria Town Phase 4'],
    ['The English Table', '0518773770', 'Bahria Town Phase 4'],
    ['Ox & Grill', '0515734114', 'Bahria Town Phase 6'],
    ['Chicago Grill', '03335461175', 'Bahria Town Civic Centre'],
    ['Houston Steakhouse', '03165339133', 'Bahria Town Phase 4'],
    ['BBQ Bazar', '0512711442', 'Bahria Town Phase 4'],
    ['Chaaye Khana Bahria', '0515733674', 'Bahria Town Phase 4'],
    ['Khiva Revolving Restaurant', '0515734313', 'Bahria Town Civic Centre'],
];

$count = 0;
foreach ($leads as $l) {
    // Check if lead exists
    $st = $pdo->prepare("SELECT id, caller_id FROM leads WHERE phone=?");
    $st->execute([$l[1]]);
    $existing = $st->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update caller if unassigned
        if (empty($existing['caller_id'])) {
            $st = $pdo->prepare("UPDATE leads SET caller_id=?, status='New' WHERE id=?");
            $st->execute([$callerId, $existing['id']]);
            $count++;
        }
    } else {
        // Create new lead
        $st = $pdo->prepare("INSERT INTO leads (name, phone, city, caller_id, status, created_at) VALUES (?,?,?,?,?,datetime('now'))");
        $st->execute([$l[0], $l[1], $l[2], $callerId, 'New']);
        $count++;
    }
}

echo "Assigned $count leads to $callerName\n";
echo "\n=== CREDENTIALS ===\n";
echo "URL: https://app.pdfpax.com/caller-crm.php\n";
echo "Email: $callerEmail\n";
echo "Password: $callerPass\n";
echo "Referral Code: " . $caller['referral_code'] . "\n";

@unlink(__FILE__);

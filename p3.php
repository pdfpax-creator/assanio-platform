<?php
// Fix leads assignment + check create option. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

// Get caller
$st = $pdo->prepare("SELECT id FROM users WHERE email='caller1@assanio.pk'");
$st->execute();
$callerId = $st->fetchColumn();
if (!$callerId) { echo "Caller not found\n"; exit; }
echo "Caller ID: $callerId\n";

// Assign leads with CORRECT column names
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
    $st = $pdo->prepare("SELECT id, assigned_to FROM leads WHERE phone=?");
    $st->execute([$l[1]]);
    $ex = $st->fetch(PDO::FETCH_ASSOC);
    if ($ex) {
        if (empty($ex['assigned_to'])) {
            $pdo->prepare("UPDATE leads SET assigned_to=?, status='new' WHERE id=?")->execute([$callerId, $ex['id']]);
            $count++;
        }
    } else {
        $pdo->prepare("INSERT INTO leads (restaurant_name, phone, city, assigned_to, status) VALUES (?,?,?,?,?)")
            ->execute([$l[0], $l[1], $l[2], $callerId, 'new']);
        $count++;
    }
}
echo "Assigned $count leads\n";

// Verify
$st = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to=?");
$st->execute([$callerId]);
echo "Total leads for caller: " . $st->fetchColumn() . "\n";

@unlink(__FILE__);

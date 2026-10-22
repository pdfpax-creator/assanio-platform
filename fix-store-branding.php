<?php
// Fix store admin branding: RestoPlatform->Assanio, dynamic store name. DELETE AFTER RUN.
$stores = [
    'tandoor-nights' => 'desi-bbq',
    'brew-and-bean' => 'cafe',
    'maison-olive' => 'fine-dining',
    'smash-bros' => 'pizza-fastfood',
    'downtown-slice' => 'pizza-fastfood',
    'br9-cafe' => 'pizza-fastfood',
];

foreach ($stores as $slug => $tpl) {
    $adminFile = __DIR__ . "/stores/$slug/admin/index.html";
    $tplFile = __DIR__ . "/templates/$tpl/admin/index.html";
    if (!file_exists($adminFile)) { echo "$slug: admin NOT FOUND\n"; continue; }
    if (!file_exists($tplFile)) { echo "$slug: template NOT FOUND\n"; continue; }
    // Copy the fixed template admin file
    copy($tplFile, $adminFile);
    echo "$slug: admin updated from $tpl template\n";
}
echo "DONE.\n";

<?php
// Patch: show actual error message. Small file, should push fine.
foreach (['desi-bbq','pizza-fastfood'] as $tpl) {
    $f = __DIR__."/templates/$tpl/index.html";
    $h = file_get_contents($f);
    $old = "Content failed to load. Please check content.json.</p>";
    $new = "Content failed: '+e.message+'</p>";
    if (strpos($h, $old) !== false) {
        $h = str_replace($old, $new, $h);
        file_put_contents($f, $h);
        echo "$tpl patched\n";
    }
    // Sync to stores
    $map = ['desi-bbq'=>['tandoor-nights'],'pizza-fastfood'=>['smash-bros']];
    foreach ($map[$tpl] as $s) {
        $d = __DIR__."/stores/$s/index.html";
        if (file_exists($d)) { copy($f, $d); echo "$s synced\n"; }
    }
}
@unlink(__FILE__);
echo "OK";

<?php
// Fix missing comma after 'truck' icon. 
foreach (['desi-bbq','pizza-fastfood'] as $tpl) {
    $f = __DIR__."/templates/$tpl/index.html";
    $h = file_get_contents($f);
    // Fix: add comma after truck line, fix qr_code_2 syntax
    $old = "'truck': '<rect x=\"1\" y=\"3\" width=\"15\" height=\"13\" rx=\"1\"/><path d=\"M16 8h4l3 3v5h-7V8z\"/><circle cx=\"5.5\" cy=\"18.5\" r=\"2.5\"/><circle cx=\"18.5\" cy=\"18.5\" r=\"2.5\"/>'\n  qr_code_2:'";
    $new = "'truck': '<rect x=\"1\" y=\"3\" width=\"15\" height=\"13\" rx=\"1\"/><path d=\"M16 8h4l3 3v5h-7V8z\"/><circle cx=\"5.5\" cy=\"18.5\" r=\"2.5\"/><circle cx=\"18.5\" cy=\"18.5\" r=\"2.5\"/>',\n  'qr_code_2': '";
    if (strpos($h, $old) !== false) {
        $h = str_replace($old, $new, $h);
        // Also fix the ending: remove trailing comma before };
        $h = str_replace("'/></svg>',};", "'/></svg>'};", $h);
        file_put_contents($f, $h);
        echo "$tpl: comma fixed\n";
    } else {
        echo "$tpl: pattern not found\n";
    }
    $map = ['desi-bbq'=>['tandoor-nights'],'pizza-fastfood'=>['smash-bros']];
    foreach ($map[$tpl] as $s) {
        $d = __DIR__."/stores/$s/index.html";
        if (file_exists($d)) { copy($f, $d); echo "$s synced\n"; }
    }
}
@unlink(__FILE__);
echo "OK";

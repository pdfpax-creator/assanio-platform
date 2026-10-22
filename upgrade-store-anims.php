<?php
// Apply animation upgrade to demo stores - run once, DELETE after
$slugs = ['tandoor-nights', 'brew-and-bean', 'maison-olive', 'smash-bros'];
$tplMap = ['tandoor-nights' => 'desi-bbq', 'brew-and-bean' => 'cafe', 'maison-olive' => 'fine-dining', 'smash-bros' => 'pizza-fastfood'];

echo "<pre>";
foreach ($slugs as $slug) {
    $storeFile = __DIR__ . "/stores/$slug/index.html";
    $tplFile = __DIR__ . "/templates/{$tplMap[$slug]}/index.html";
    if (!file_exists($storeFile) || !file_exists($tplFile)) {
        echo "⏭️ $slug: skip\n"; continue;
    }
    // Copy the animation CSS and JS from template
    $tplHtml = file_get_contents($tplFile);
    $storeHtml = file_get_contents($storeFile);

    // Extract new CSS (from .rv{ to .no-js .rv)
    preg_match('/\.rv\{opacity:0;transition.*?\n\.no-js \.rv\{[^}]+\}/s', $tplHtml, $cssMatch);
    // Extract new JS initAnims
    preg_match('/function initAnims\(c\)\{.*?requestAnimationFrame\(sh\)\}\},\{passive:true\}\);sh\(\);\n\}/s', $tplHtml, $jsMatch);

    if ($cssMatch && $jsMatch) {
        // Replace old CSS
        $storeHtml = preg_replace('/\.rv\{opacity:0;transform:translateY\(24px\);.*?\n\.rv-on\{[^}]+\}/s', $cssMatch[0], $storeHtml);
        // Replace old JS - find initAnims
        $start = strpos($storeHtml, 'function initAnims(c){');
        if ($start !== false) {
            $depth = 0; $end = $start;
            for ($i = $start; $i < strlen($storeHtml); $i++) {
                if ($storeHtml[$i] == '{') $depth++;
                elseif ($storeHtml[$i] == '}') { $depth--; if ($depth == 0) { $end = $i+1; break; } }
            }
            $storeHtml = substr($storeHtml, 0, $start) . $jsMatch[0] . substr($storeHtml, $end);
        }
        // Add no-js to body
        $storeHtml = preg_replace('/<body(?![^>]*class)/', '<body class="no-js"', $storeHtml, 1);

        file_put_contents($storeFile, $storeHtml);
        echo "✅ $slug: animations upgraded\n";
    } else {
        echo "❌ $slug: could not extract\n";
    }
}
echo "DONE!</pre>";
@unlink(__FILE__);

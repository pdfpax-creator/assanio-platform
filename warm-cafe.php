<?php
// Make Brew & Bean feel WARM & REAL (not luxury) - run once, DELETE after
$file = __DIR__ . '/stores/brew-and-bean/content.json';
if (!file_exists($file)) die("Not found");
$data = json_decode(file_get_contents($file), true);

// Warmer, more direct hero
$data['hero']['headline'] = "Chai, Coffee & Good Conversations.";
$data['hero']['subtext'] = "Freshly brewed coffee, warm croissants, and a cozy corner to unwind. Come as you are — stay as long as you like.";
$data['hero']['badge'] = "Fresh Coffee & Bakery — F-7 Markaz, Islamabad";

// Warmer about section
if (isset($data['about'])) {
    $data['about']['heading'] = "Your Neighbourhood Cafe.";
    $data['about']['para1'] = "Brew & Bean started with a simple idea — great coffee shouldn't be complicated. We brew fresh every morning, bake throughout the day, and treat everyone like a regular.";
    $data['about']['para2'] = "Whether it's your morning chai, an afternoon pick-me-up, or a late-night catch-up with friends, there's always a warm cup waiting for you here.";
}

file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "✅ Brew & Bean warmed up! DELETE THIS FILE.";
@unlink(__FILE__);

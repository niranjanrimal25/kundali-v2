<?php

use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\Interpretation\ReadingGenerator;
use Illuminate\Contracts\Console\Kernel;

// Regenerates /home/user/full-reading-sample.html from the Demo Chart.
// Run: php generate-sample.php   (from the project root)
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$k = Kundali::firstOrCreate(
    ['name' => 'Demo Chart'],
    ['user_id' => User::first()->id,
        'birth_date' => '1990-05-15', 'birth_time' => '10:30:00',
        'birth_place' => 'Pokhara, Nepal', 'latitude' => 28.26689,
        'longitude' => 83.96851, 'timezone' => 'Asia/Kathmandu']
);

$s = app(ReadingGenerator::class)
    ->forKundali($k, 'en', true);

$h = '<!doctype html><meta charset=utf-8><title>Full Reading</title>';
$h .= '<style>body{background:#f4f1ea;font-family:Georgia,serif;margin:0;padding:40px 20px}'
    .'.w{max-width:780px;margin:0 auto;background:#fffdf8;border:1px solid #e3dccd;border-radius:6px;padding:48px}'
    .'h1{color:#4a2c5a;margin:0 0 4px;font-size:30px}.sub{color:#7b3f61;font-size:14px;margin-bottom:34px}'
    .'h2{color:#4a2c5a;font-size:20px;border-top:1px solid #ede5d6;padding-top:26px;margin-top:34px}'
    .'h2:first-of-type{border:0}.st{color:#b5643f;font-size:13px;font-style:italic;margin:-10px 0 14px}'
    .'p{font-size:15px;line-height:1.75;color:#2b2520}</style>';
$h .= '<div class=w><h1>'.e($k->name).'</h1><div class=sub>Full horoscope reading &mdash; all twelve bhavas</div>';
foreach ($s as $sec) {
    $h .= '<h2>'.e($sec['title']).'</h2>';
    if (! empty($sec['subtitle'])) {
        $h .= '<div class=st>'.e($sec['subtitle']).'</div>';
    }
    foreach ($sec['paragraphs'] as $p) {
        $h .= '<p>'.e($p).'</p>';
    }
}
$h .= '</div>';
file_put_contents('/home/user/full-reading-sample.html', $h);
echo strlen($h).' bytes, '.count($s)." sections\n";

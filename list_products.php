<?php

use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

foreach (Product::with('category')->get() as $p) {
    echo $p->id.' | '.($p->category->name ?? 'N/A').' | '.$p->name."\n";
}

<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $html = view('socialevents::teams.pdf.match_sheet', ['data' => [
        'event_name' => 'Test',
        'edition_name' => 'Test Edition',
        'local_team' => 'Team A',
        'visitor_team' => 'Team B',
        'local_manager' => 'Manager A',
        'visitor_manager' => 'Manager B',
        'match_date' => '2026-07-14',
        'location' => 'Cancha',
    ]])->render();
    echo 'HTML OK: ' . strlen($html) . ' bytes';
} catch (Exception $e) {
    echo 'View Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

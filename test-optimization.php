<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\Exam\OptimizedCorrectionService;

// Activer le log des requêtes
DB::enableQueryLog();

try {
    $service = app(OptimizedCorrectionService::class);
    $data = $service->getReviewData(1, 4, 6);
    
    $queries = DB::getQueryLog();
    echo "✅ Nombre de requêtes SQL: " . count($queries) . "\n\n";
    
    echo "Détails des requêtes:\n";
    foreach ($queries as $index => $query) {
        echo ($index + 1) . ". " . $query['query'] . "\n";
        echo "   Temps: " . $query['time'] . "ms\n\n";
    }
    
    echo "\n✅ Données récupérées avec succès!\n";
    echo "Assignment ID: " . $data['assignment']['id'] . "\n";
    echo "Student: " . $data['student']['name'] . "\n";
    echo "Exam: " . $data['exam']['title'] . "\n";
    echo "Total questions: " . $data['totalQuestions'] . "\n";
    echo "Total points: " . $data['totalPoints'] . "\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

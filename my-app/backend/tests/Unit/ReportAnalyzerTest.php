<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\HardwareReport;
use App\Services\ReportAnalyzer;

class ReportAnalyzerTest extends TestCase
{
    public function test_detects_zero_gpu_power()
    {
        // 1. Подготовим модель
        $report = new HardwareReport([
            'cpu_score' => 50,
            'gpu_score' => 0,
            'ram_gb'    => 32,
        ]);

        // 2. Подменим конфиг, чтобы был ключ "test_task"
        config(['thresholds.tasks.test_task' => [
            'cpu_score' => ['min' => 0],
            'gpu_score' => ['min' => 1],
            'ram_gb'    => ['min' => 1],
        ]]);

        // 3. Вызовем анализ
        $feedback = ReportAnalyzer::analyze($report, 'test_task');

        // 4. Убедимся, что в проблемах есть GPU
        $this->assertIsArray($feedback['problems']);
        $this->assertStringContainsString('GPU мощность', implode(' ', $feedback['problems']));
    }
}

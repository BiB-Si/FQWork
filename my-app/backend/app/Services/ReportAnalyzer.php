<?php

namespace App\Services;

use App\Models\HardwareReport;
use App\Models\Component\Component;
use Illuminate\Support\Facades\Log;

class ReportAnalyzer
{
    /**
     * Выполняет полный анализ ранее распарсенного отчёта.
     *
     * @param HardwareReport $report  Модель с полем report_data (массив характеристик)
     * @param string         $taskKey Ключ задачи из конфига, например 'modeling'
     * @return array              [
     *    'problems'    => [строки с описанием проблем],
     *    'recommendations' => [строки с рекомендациями],
     * ]
     */
    public static function analyze(HardwareReport $report, string $taskKey): array
    {
        // 1. Получаем настройки из config/thresholds.php
        $allTasks = config('thresholds.tasks', []);
        if (!isset($allTasks[$taskKey])) {
            throw new \InvalidArgumentException("Задача \"$taskKey\" не найдена в конфигурации.");
        }

        $taskConfig = $allTasks[$taskKey];
        $thresholds = $taskConfig['thresholds'];
        $priority   = $taskConfig['priority_order'];
        $suggestions= $taskConfig['upgrade_suggestions'];

        // 2. Получаем распарсенные данные отчёта
        //    Предполагаем, что $report->report_data — это PHP-массив с ключами:
        //    ['cpu_cores'=>int, 'ram_gb'=>int, 'gpu_vram_gb'=>int, 'ssd_type'=>string]
        $data = $report->report_data;

        // Если report_data — JSON, Laravel автоматически конвертирует его в массив при доступе
        $cpuCores   = $data['cpu_cores']   ?? null;
        $ramGb      = $data['ram_gb']      ?? null;
        $gpuVramGb  = $data['gpu_vram_gb'] ?? null;
        $ssdType    = $data['ssd_type']    ?? null;

        $problems = [];
        $details  = []; // Для внутреннего использования (какие имена параметров ниже порога)

        // 3. Сравнение с порогами:
        if ($cpuCores !== null && $cpuCores < $thresholds['cpu_cores']) {
            $problems[] = "CPU: {$cpuCores} ядер (< {$thresholds['cpu_cores']}) — слишком мало ядер для задачи.";
            $details['cpu'] = $cpuCores;
        }
        if ($ramGb !== null && $ramGb < $thresholds['ram_gb']) {
            $problems[] = "RAM: {$ramGb} ГБ (< {$thresholds['ram_gb']}) — недостаточно оперативной памяти.";
            $details['ram'] = $ramGb;
        }
        if ($gpuVramGb !== null && $gpuVramGb < $thresholds['gpu_vram_gb']) {
            $problems[] = "GPU VRAM: {$gpuVramGb} ГБ (< {$thresholds['gpu_vram_gb']}) — неудовлетворительный объём видеопамяти.";
            $details['gpu'] = $gpuVramGb;
        }
        if ($ssdType !== null && strtolower($ssdType) !== strtolower($thresholds['ssd_type'])) {
            $problems[] = "SSD: {$ssdType} (< требуемого {$thresholds['ssd_type']}) — рекомендуется SSD типа {$thresholds['ssd_type']}.";
            $details['ssd'] = $ssdType;
        }

        // 4. Общая рекомендация с порогами
        $minReq = "Рекомендуемый минимум: CPU {$thresholds['cpu_cores']} ядер / RAM {$thresholds['ram_gb']} ГБ / " .
                  "GPU VRAM {$thresholds['gpu_vram_gb']} ГБ / SSD {$thresholds['ssd_type']}.";
        // 5. Определяем главное “узкое звено” по приоритету
        $upgradeTarget = null;
        foreach ($priority as $param) {
            if (isset($details[$param])) {
                $upgradeTarget = $param;
                break;
            }
        }

        $recs = [];
        if ($upgradeTarget) {
            $options = $suggestions[$upgradeTarget] ?? [];
            $chosen = count($options) ? $options[array_rand($options)] : null;
            $recs[] = "Самое критичное узкое место: {$upgradeTarget}. Рекомендуемый апгрейд: "
                    . ($chosen ? $chosen : 'уточните модель вручную');
        } else {
            $recs[] = "Все параметры соответствуют минимальным требованиям.";
        }

        // Если есть какие-то проблемы, собираем их в текущее сообщение
        if (!empty($problems)) {
            array_unshift($recs, implode(' ', $problems));
            $recs[] = $minReq;
        } else {
            $recs[] = $minReq;
        }

        return [
            'problems'        => $problems,
            'recommendations' => $recs,
        ];
    }
}

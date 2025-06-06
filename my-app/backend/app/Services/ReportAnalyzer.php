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
        
        $cpuFreqMhz  = $data['cpu_freq_mhz'] ?? 0;
        $gpuClockMhz = $data['gpu_clock_mhz'] ?? 0;
        $gpuBusWidth = $data['gpu_bus_width'] ?? 0;

        $cpuScore = 0;
        if ($cpuCores !== null && $cpuFreqMhz > 0) {
            $cpuScore = ($cpuCores * $cpuFreqMhz) / 1000;
        }

        $gpuScore = 0;
        if ($gpuVramGb !== null && $gpuBusWidth > 0 && $gpuClockMhz > 0) {
            $gpuScore = ($gpuVramGb * 10) + ($gpuBusWidth / 8) + ($gpuClockMhz / 100);
        }

        $problems = [];
        $details  = []; // Для внутреннего использования (какие имена параметров ниже порога)

        // 3. Сравнение с порогами:
        if ($cpuCores !== null && $cpuCores < $thresholds['cpu_cores']) {
            $problems[] = "CPU: {$cpuCores} ядер (< {$thresholds['cpu_cores']}) — слишком мало ядер для задачи.";
            $details['cpu'] = $cpuCores;
        }
        if ($cpuScore < ($thresholds['cpu_score'] ?? 10)) {
            $problems[] = "CPU мощность: {$cpuScore} баллов (< {$thresholds['cpu_score']}) — недостаточно вычислительной мощности для задачи.";
            $details['cpu_score'] = $cpuScore;
        }
        if ($ramGb !== null && $ramGb < $thresholds['ram_gb']) {
            $problems[] = "RAM: {$ramGb} ГБ (< {$thresholds['ram_gb']}) — недостаточно оперативной памяти.";
            $details['ram'] = $ramGb;
        }
        if ($gpuVramGb !== null && $gpuVramGb < $thresholds['gpu_vram_gb']) {
            $problems[] = "GPU VRAM: {$gpuVramGb} ГБ (< {$thresholds['gpu_vram_gb']}) — неудовлетворительный объём видеопамяти.";
            $details['gpu'] = $gpuVramGb;
        }
        
        if ($gpuScore < ($thresholds['gpu_score'] ?? 10)) {
            $problems[] = "GPU мощность: {$gpuScore} баллов (< {$thresholds['gpu_score']}) — видеоподсистема недостаточно мощная для задачи.";
            $details['gpu_score'] = $gpuScore;
        }

        if ($ssdType !== null) {
            $ssdPriority = [
                'hdd'  => 1,
                'sata' => 2,
                'nvme' => 3,
            ];

            $actual = strtolower($ssdType);
            $required = strtolower($thresholds['ssd_type']);

            if (
                isset($ssdPriority[$actual], $ssdPriority[$required]) &&
                $ssdPriority[$actual] < $ssdPriority[$required]
            ) {
                $problems[] = "SSD: {$ssdType} (< требуемого {$thresholds['ssd_type']}) — рекомендуется SSD типа {$thresholds['ssd_type']}.";
                $details['ssd'] = $ssdType;
            }
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

        if (!empty($problems)) {
            // Отдельные рекомендации по каждому пункту
            foreach ($details as $param => $value) {
                switch ($param) {
                    case 'cpu':
                        $recs[] = "Рекомендуется перейти на CPU с большим количеством ядер, например: " . ($suggestions['cpu'][0] ?? 'более мощный процессор');
                        break;
                    case 'ram':
                        $recs[] = "Рекомендуется увеличить объём оперативной памяти, например: " . ($suggestions['ram'][0] ?? 'добавить ОЗУ');
                        break;
                    case 'gpu':
                        $recs[] = "Рекомендуется более мощная видеокарта, например: " . ($suggestions['gpu'][0] ?? 'другая видеокарта');
                        break;
                    case 'ssd':
                        $recs[] = "Рекомендуется использовать накопитель типа " . ($thresholds['ssd_type'] ?? 'SSD');
                        break;
                }
            }
            $recs[] = $minReq;
        } else {
            $recs[] = "Все параметры соответствуют минимальным требованиям.";
            $recs[] = $minReq;
        }

        // Log::info('ReportAnalyzer debug', [
        //     'data' => $data,
        //     'cpu_score' => $cpuScore ?? null,
        //     'gpu_score' => $gpuScore ?? null,
        //     'cpu_freq_mhz' => $cpuFreqMhz ?? null,
        //     'gpu_clock_mhz' => $gpuClockMhz ?? null,
        //     'gpu_bus_width' => $gpuBusWidth ?? null,
        // ]);
        return [
            'problems'        => $problems,
            'recommendations' => $recs,
        ];
    }
}


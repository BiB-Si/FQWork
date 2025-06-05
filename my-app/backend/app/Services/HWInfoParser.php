<?php

namespace App\Services;

class HWInfoParser
{
    /**
     * Парсит HWInfo-/AIDA64-отчёт (CSV/HTML/XML) в формате "ключ","значение" (одна пара на строку).
     * Извлекает характеристики системы.
     *
     * @param string $filePath Абсолютный путь к файлу отчёта
     * @return array
     */
    public static function parse(string $filePath): array
    {
        $cpuCores     = 0;
        $ramGb        = 0;
        $gpuVramMb    = [];
        $ssdType      = 'Unknown';
        $cpuFreqMhz   = 0;
        $gpuClockMhz  = 0;
        $gpuBusWidth  = 0;
        $cpuFreqMhz  = 0;
        
        $handle = @fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception("Не удалось открыть файл: {$filePath}");
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = str_getcsv($line, ',', '"');
            if (count($parts) < 2) {
                continue;
            }

            $key   = trim($parts[0], "\" \t\n\r");
            $value = trim($parts[1], "\" \t\n\r");

            $lowerKey = mb_strtolower($key);
            $lowerVal = mb_strtolower($value);

            // CPU: Количество ядер
            if (mb_strpos($lowerKey, 'количество ядер процессора') !== false) {
                if (preg_match('/\d+/', $value, $m)) {
                    $cpuCores = (int)$m[0];
                }
                continue;
            }

            // RAM
            if (mb_strpos($lowerKey, 'общий объем памяти') !== false || mb_strpos($lowerKey, 'общий объём памяти') !== false) {
                if (preg_match('/(\d+)\s*(гб|gb)/ui', $value, $m)) {
                    $ramGb = (int)$m[1];
                }
                continue;
            }

            // GPU VRAM
            if (mb_strpos($lowerKey, 'видеопамять') !== false) {
                if (preg_match('/(\d+)\s*мб/ui', $value, $m)) {
                    $gpuVramMb[] = (int)$m[1];
                }
                continue;
            }

            // SSD Тип
            if (mb_strpos($lowerKey, 'модель накопителя') !== false || mb_strpos($lowerKey, 'имя устройства') !== false || mb_strpos($lowerKey, 'хост-контроллер') !== false) {
                if (mb_strpos($lowerVal, 'nvme') !== false) {
                    $ssdType = 'NVMe';
                } elseif (mb_strpos($lowerVal, 'ssd') !== false && $ssdType !== 'NVMe') {
                    $ssdType = 'SATA';
                }
                continue;
            }

            // 6) CPU частота
            if (mb_strpos($lowerKey, 'исходная частота процессора') !== false ||
                mb_strpos($lowerKey, 'текущая частота') !== false && mb_strpos($lowerVal, 'мгц') !== false) {
                if (preg_match('/([\d.]+)\s*мгц/ui', $value, $m)) {
                    $cpuFreqMhz = (int) floatval($m[1]);
                }
                continue;
            }

            // 7) GPU частота (используем текущую частоту памяти как приближение)
            if (mb_strpos($lowerKey, 'текущая частота памяти') !== false) {
                if (preg_match('/([\d.]+)\s*мгц/ui', $value, $m)) {
                    $gpuClockMhz = (int) floatval($m[1]);
                }
                continue;
            }

            // 8) GPU ширина шины
            if (mb_strpos($lowerKey, 'ширина шины') !== false && mb_strpos($lowerVal, 'bits') !== false) {
                if (preg_match('/(\d+)\s*bits?/i', $value, $m)) {
                    $gpuBusWidth = (int) $m[1];
                }
                continue;
            }
        }
        fclose($handle);

        $gpuVramGb = !empty($gpuVramMb) ? (int)floor(max($gpuVramMb) / 1024) : 0;
        // Пробуем найти ширину шины (если встречалась строка «Ширина шины:» или «width»)
        
        if ($gpuClockMhz === 0) {
            $gpuClockMhz = 500; // по умолчанию 500 МГц, если не указано в отчёте
        }


        return [
            'cpu_cores'     => $cpuCores,
            'cpu_freq_mhz'  => $cpuFreqMhz,
            'ram_gb'        => $ramGb,
            'gpu_vram_gb'   => $gpuVramGb,
            'gpu_clock_mhz' => $gpuClockMhz,
            'gpu_bus_width' => $gpuBusWidth,
            'ssd_type'      => $ssdType,
        ];

    }
}

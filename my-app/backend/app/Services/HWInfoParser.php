<?php

namespace App\Services;

class HWInfoParser
{
    /**
     * Парсит HWInfo-/AIDA64-отчёт (CSV/HTML/XML) в формате "ключ","значение" (одна пара на строку).
     * Извлекает именно четыре значения:
     *   - cpu_cores   (int)  — Количество ядер процессора
     *   - ram_gb      (int)  — Объём оперативной памяти (в ГБ)
     *   - gpu_vram_gb (int)  — Объём видеопамяти (максимальный из найденных, в ГБ)
     *   - ssd_type    (string) — NVMe или SATA или 'Unknown'
     *
     * @param string $filePath Абсолютный путь к файлу отчёта
     * @return array{cpu_cores:int, ram_gb:int, gpu_vram_gb:int, ssd_type:string}
     * @throws \Exception при ошибках чтения/парсинга
     */
    public static function parse(string $filePath): array
    {
        // Сразу инициализируем значения «по умолчанию»
        $cpuCores    = 0;
        $ramGb       = 0;
        $gpuVramMb   = [];    // будем хранить массив всех найденных значений видеопамяти в МБ
        $ssdType     = 'Unknown';

        // 1) Читаем файл построчно
        $handle = @fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception("Не удалось открыть файл: {$filePath}");
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue; // пропускаем пустые строки
            }

            // Каждая строка в формате: "Ключ:","Значение"
            // Удалим начальные и конечные кавычки, если они есть
            // Разбиваем строку по разделителю "," (первая пара — ключ, вторая — значение)
            // Но поскольку сами кавычки могут быть вокруг значения, сделаем так:
            $parts = str_getcsv($line, ',', '"');
            // После этого $parts[0] — "Ключ:", $parts[1] — "Значение"

            if (count($parts) < 2) {
                continue;
            }
            $key   = trim($parts[0], "\" \t\n\r");   // убирать кавычки и пробелы
            $value = trim($parts[1], "\" \t\n\r");

            $lowerKey = mb_strtolower($key);
            $lowerVal = mb_strtolower($value);

            // 2) CPU: «Количество ядер процессора:»
            if (mb_strpos($lowerKey, 'количество ядер процессора') !== false) {
                // Обычно value — просто число, например "4" или "8"
                if (preg_match('/\d+/', $value, $m)) {
                    $cpuCores = (int)$m[0];
                }
                continue;
            }

            // 3) RAM: «Общий объём памяти:»
            if (mb_strpos($lowerKey, 'общий объём памяти') !== false ||
                mb_strpos($lowerKey, 'общий объем памяти') !== false) {
                // value может быть: "20 ГБ" или "16 GB" и т.п.
                if (preg_match('/(\d+)\s*(гб|gb)/ui', $value, $m)) {
                    $ramGb = (int)$m[1];
                }
                continue;
            }

            // 4) GPU VRAM: «Видеопамять:»
            if (mb_strpos($lowerKey, 'видеопамять') !== false) {
                // value может быть: "4096 МБ из GDDR5 SDRAM [Samsung]" или "2048 МБ из DDR4 SDRAM"
                if (preg_match('/(\d+)\s*мб/ui', $value, $m)) {
                    $gpuVramMb[] = (int)$m[1];
                }
                continue;
            }

            // 5) SSD: если ключ содержит «NVMe», или «Модель накопителя» и в значении «NVMe», то NVMe
            if (mb_strpos($lowerKey, 'имя устройства') !== false ||
                mb_strpos($lowerKey, 'модель накопителя') !== false ||
                mb_strpos($lowerKey, 'хост-контроллер') !== false) {
                // если хоть где-то встретим «nvme» → сразу устанавливаем NVMe
                if (mb_strpos($lowerVal, 'nvme') !== false) {
                    $ssdType = 'NVMe';
                    continue;
                }
                // иначе, если слово «ssd» есть (но без nvme) → полагаем SATA
                if (mb_strpos($lowerVal, 'ssd') !== false && $ssdType !== 'NVMe') {
                    $ssdType = 'SATA';
                    // Не продолжаем искать, т.к. возможен NVMe позже
                }
                continue;
            }
        }
        fclose($handle);

        // 6) Если нашли несколько значений VRAM (МБ), то берём максимальное
        $maxVramMb = 0;
        if (!empty($gpuVramMb)) {
            $maxVramMb = max($gpuVramMb);
        }
        // Переводим в ГБ (округляем вниз)
        $gpuVramGb = (int) floor($maxVramMb / 1024);

        // Если в итоге SSD всё ещё 'Unknown', можно проверить, нет ли в других строках «SSD» без «NVMe»
        // (Но большинству консолей достаточно того, что найдём NVMe первый.) 

        // Возвращаем итоговый массив
        return [
            'cpu_cores'   => $cpuCores,
            'ram_gb'      => $ramGb,
            'gpu_vram_gb' => $gpuVramGb,
            'ssd_type'    => $ssdType,
        ];
    }
}

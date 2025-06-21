<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\HWInfoParser;

class HWInfoParserTest extends TestCase
{
    public function test_parse_single_line_csv()
    {
        // 1. Сгенерируем во временную папку CSV-файл
        $path = sys_get_temp_dir() . '/hwinfo.csv';
        file_put_contents($path, "cpu_cores,cpu_freq_mhz,ram_gb\n4,3200,16");

        // 2. Вызовем парсер
        $parser = new HWInfoParser();
        $result = $parser->parse($path);

        // 3. Проверим результат
        $this->assertIsArray($result);
        $this->assertEquals(4,    $result['cpu_cores']);
        $this->assertEquals(3200, $result['cpu_freq_mhz']);
        $this->assertEquals(16,   $result['ram_gb']);
    }
}

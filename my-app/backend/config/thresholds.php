<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Пороговые значения и рекомендации для разных целевых задач
    |--------------------------------------------------------------------------
    |
    | Структура:
    | 'tasks' => [
    |     'modeling' => [
    |         'thresholds' => [ ... ],
    |         'priority_order' => [ ... ],
    |         'upgrade_suggestions' => [ ... ],
    |     ],
    |     '1c' => [ ... ],
    |     'video_edit' => [ ... ],
    |     // Можно добавить любую новую задачу в будущем
    | ],
    */

    'tasks' => [
        'modeling' => [
            'thresholds' => [
                'cpu_cores'   => 8,
                'cpu_score'    => 24, // 8 × 3000 / 1000
                'ram_gb'      => 32, 
                'gpu_vram_gb' => 8,  
                'gpu_score'    => 125, // 8GB×10 + 256bit/8 + 1500MHz/100 = 125
                'ssd_type'    => 'NVMe',
            ],
            'priority_order' => ['ram', 'gpu', 'cpu', 'ssd'],
            'upgrade_suggestions' => [
                'cpu' => ['Ryzen 7 7700X', 'Core i7-13700K'],
                'ram' => ['Corsair 2×16GB 3600MHz', 'Kingston 2×16GB DDR4'],
                'gpu' => ['NVIDIA RTX 3060 12GB', 'AMD RX 6700 XT 12GB'],
                'ssd' => ['Samsung 970 EVO Plus 1TB', 'WD SN850 1TB'],
            ],
        ],

        '1c' => [
            // Пример минимальных порогов для базовых ERP-систем (1С)
            'thresholds' => [
                'cpu_cores'   => 4,
                'cpu_score'    => 10,     // 4 × 2500 / 1000
                'ram_gb'      => 8,
                'gpu_vram_gb' => 2,
                'gpu_score'    => 40,     // 2GB×10 + 128bit/8 + 1200MHz/100 = 40
                'ssd_type'    => 'SATA', 
            ],
            'priority_order' => ['ram', 'cpu', 'ssd', 'gpu'],
            'upgrade_suggestions' => [
                'cpu' => ['Core i5-12400', 'Ryzen 5 5600X'],
                'ram' => ['16GB DDR4 3200MHz', '2×8GB DDR4 3000MHz'],
                'gpu' => ['GTX 1650 4GB', 'GTX 1050 Ti 4GB'],
                'ssd' => ['Samsung 860 EVO 500GB', 'Crucial MX500 1TB'],
            ],
        ],

        'video_edit' => [
            // Пороговые значения для видеомонтажа (Premiere Pro, DaVinci)
            'thresholds' => [
                'cpu_cores'   => 6,
                'cpu_score'    => 18,     // 6 × 3000 / 1000
                'ram_gb'      => 16,
                'gpu_vram_gb' => 6,
                'gpu_score'    => 105,    // 6GB×10 + 192bit/8 + 1600MHz/100 = 105
                'ssd_type'    => 'NVMe',
            ],
            'priority_order' => ['gpu', 'ram', 'cpu', 'ssd'],
            'upgrade_suggestions' => [
                'cpu' => ['Ryzen 5 5600X', 'Core i5-12600K'],
                'ram' => ['16GB DDR4 3200MHz', '32GB DDR4 3200MHz'],
                'gpu' => ['NVIDIA RTX 2060 6GB', 'AMD RX 6600 XT 8GB'],
                'ssd' => ['Samsung 970 EVO Plus 500GB', 'ADATA XPG SX8200 512GB'],
            ],
        ],

        // Добавьте сюда другие задачи для будущего расширения
        // 'data_analysis' => [ ... ],
        // 'gaming' => [ ... ],
    ],
];

<?php

namespace App\Http\Controllers;

use App\Models\HardwareReport;
use App\Services\ReportAnalyzer;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * Возвращает JSON с рекомендациями по апгрейду для конкретного отчёта и задачи.
     *
     * @param int    $id   ID отчёта (hardware_reports.id)
     * @param string $task Ключ задачи, например 'modeling', '1c', 'video_edit'
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecommendations($id, $task)
    {
        // 1. Ищем отчет в базе
        $report = HardwareReport::find($id);
        if (!$report) {
            return response()->json(['error' => "Отчёт с ID {$id} не найден"], 404);
        }

        // 2. Проверяем, действительно ли report_data есть (ранее файл должен быть распарсен)
        if (empty($report->report_data)) {
            return response()->json(['error' => 'Данные отчёта отсутствуют или не распарсены'], 422);
        }

        // 3. Вызываем сервис анализа
        try {
            $result = ReportAnalyzer::analyze($report, $task);
            return response()->json($result, 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Внутренняя ошибка сервера'], 500);
        }
    }
}

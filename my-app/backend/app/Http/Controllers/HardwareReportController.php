<?php
namespace App\Http\Controllers;

use App\Models\HardwareReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\HWInfoParser; // CSV-парсер, который возвращает базовые данные
use App\Services\ReportAnalyzer;      // сервис, который сравнивает с порогами и выдаёт рекомендации

class HardwareReportController extends Controller
{
    /**
     * Загрузка отчёта и мгновенный запуск анализа по порогам.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
{
    $validated = $request->validate([
        'report'   => 'required|file|mimes:txt,csv,html,xml',
        'build_id' => 'required|integer|exists:builds,id',
        'task'     => 'required|string|in:modeling,1c,video_edit',
    ]);

    // Сохраняем файл
    $path = $request->file('report')->store('hwinfo_reports');

    // Создаём запись
    $report = new HardwareReport();
    $report->build_id = $validated['build_id'];
    $report->status   = 'pending';
    $report->save();

    try {
        // Парсим
        $basicData = HWInfoParser::parse(storage_path('app/' . $path));
        $report->report_data = $basicData;
        $report->status      = 'parsed';
        $report->save();

        // Анализируем с выбранной задачей
        $analysisResult = ReportAnalyzer::analyze($report, $validated['task']);

        return response()->json([
            'basic_data' => $basicData,
            'analysis'   => $analysisResult,
        ], 200);

    } catch (\Exception $e) {
        $report->status = 'failed';
        $report->save();
        return response()->json(['error' => 'Ошибка: ' . $e->getMessage()], 500);
    }
}


    // Список отчётов (можно добавить фильтр по build_id)
    public function index(Request $request)
    {
        $query = HardwareReport::query();
        if ($request->has('build_id')) {
            $query->where('build_id', $request->query('build_id'));
        }
        $reports = $query->with('issues')->paginate(10);
        return response()->json($reports);
    }

    public function getRecommendations(int $reportId, string $task)
    {
        $report = HardwareReport::findOrFail($reportId);

        // Запускаем анализ
        $result = ReportAnalyzer::analyze($report, $task);

        // Добавим базовые параметры вручную
        $data = $report->report_data ?? [];

        return response()->json([
            'problems'        => $result['problems'],
            'recommendations' => $result['recommendations'],
            'cpu_score'       => $result['cpu_score'] ?? null,
            'gpu_score'       => $result['gpu_score'] ?? null,
            'cpu_cores'       => $data['cpu_cores'] ?? null,
            'ram_gb'          => $data['ram_gb'] ?? null,
            'gpu_vram_gb'     => $data['gpu_vram_gb'] ?? null,
            'ssd_type'        => $data['ssd_type'] ?? null,
        ]);
    }


    // Получить один отчёт
    public function show($id)
    {
        $report = HardwareReport::with('issues')->findOrFail($id);
        return response()->json($report);
    }
}

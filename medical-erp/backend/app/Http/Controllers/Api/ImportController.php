<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\EmployeeImport;
use App\Imports\EmployeeWorkbookImport;
use App\Imports\TamheerImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    /**
     * POST /api/import/employees
     * استيراد بيانات الموظفين من ملف إكسل
     *
     * يدعم:
     * - ملف إكسل (.xlsx, .xls) بورقة واحدة أو ورقتين (موظفين + تمهير)
     * - ملف CSV
     *
     * الأعمدة المدعومة (عربي/إنجليزي):
     * اسم الموظف | الرقم الوظيفي | المسمى الوظيفي | رقم الهوية
     * الإجازات المستحقة | الإجازات المستهلكة
     * تاريخ بداية العقد | تاريخ نهاية العقد | الراتب
     */
    public function importEmployees(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10MB
            'type' => 'in:all,employees,tamheer',
        ]);

        $type = $request->input('type', 'all');

        try {
            if ($type === 'tamheer') {
                $import = new TamheerImport();
                Excel::import($import, $request->file('file'));
                $results = ['tamheer' => $import->getResults()];
            } elseif ($type === 'employees') {
                $import = new EmployeeImport();
                Excel::import($import, $request->file('file'));
                $results = ['employees' => $import->getResults()];
            } else {
                // محاولة استيراد كامل (ورقتين)
                $import = new EmployeeWorkbookImport();
                Excel::import($import, $request->file('file'));
                $results = $import->getResults();
            }

            $totalImported = collect($results)->sum(fn($r) => $r['imported'] ?? 0);
            $totalUpdated = collect($results)->sum(fn($r) => $r['updated'] ?? 0);
            $totalSkipped = collect($results)->sum(fn($r) => $r['skipped'] ?? 0);
            $totalErrors = collect($results)->flatMap(fn($r) => $r['errors'] ?? [])->count();

            return response()->json([
                'success' => true,
                'message' => "تم استيراد {$totalImported} سجل جديد، تحديث {$totalUpdated} سجل",
                'data'    => [
                    'summary' => [
                        'imported' => $totalImported,
                        'updated'  => $totalUpdated,
                        'skipped'  => $totalSkipped,
                        'errors'   => $totalErrors,
                    ],
                    'details' => $results,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في استيراد الملف',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/import/template
     * تحميل قالب إكسل فارغ للاستيراد
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $templatePath = storage_path('app/templates/employee_import_template.xlsx');

        if (!file_exists($templatePath)) {
            // إنشاء قالب فارغ
            $this->generateTemplate($templatePath);
        }

        return response()->download($templatePath, 'employee_import_template.xlsx');
    }

    protected function generateTemplate(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ورقة الموظفين
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('بيانات الموظفين');
        $sheet1->setRightToLeft(true);

        $headers = [
            'اسم الموظف', 'الرقم الوظيفي', 'تاريخ الالتحاق', 'المسمى الوظيفي',
            'رقم الهوية', 'الإجازات المستحقة', 'الإجازات المستهلكة',
            'تاريخ بداية اخر عقد', 'تاريخ نهاية اخر عقد', 'الراتب',
        ];

        foreach ($headers as $col => $header) {
            $sheet1->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet1->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
        }

        // تنسيق الترويسة
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet1->getStyle('A1:J1')->applyFromArray($headerStyle);

        // ورقة التمهير
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('متدربات تمهير');
        $sheet2->setRightToLeft(true);

        $tamheerHeaders = [
            'اسم المتدربة', 'الرقم الوظيفي', 'تاريخ بداية التدريب',
            'تاريخ نهاية التدريب', 'مسمى التدريب', 'رقم الهوية', 'الراتب',
        ];

        foreach ($tamheerHeaders as $col => $header) {
            $sheet2->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet2->getColumnDimensionByColumn($col + 1)->setAutoSize(true);
        }
        $sheet2->getStyle('A1:G1')->applyFromArray($headerStyle);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);
    }
}

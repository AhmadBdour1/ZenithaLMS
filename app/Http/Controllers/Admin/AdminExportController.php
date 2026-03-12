<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\AuraProduct;
use App\Models\AuraOrder;
use App\Models\AuraPage;
use App\Models\Certificate;
use App\Models\Stuff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminExportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Export entity data
     */
    public function exportEntity(Request $request, $entity)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,xlsx,pdf,json,word',
            'columns' => 'nullable|array',
            'filters' => 'nullable|array',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|string',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $this->getEntityQuery($entity);

        // Apply filters
        if ($request->has('filters')) {
            foreach ($request->filters as $key => $value) {
                $this->applyFilter($query, $entity, $key, $value);
            }
        }

        // Apply date filter
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply status filter
        if ($request->status) {
            $this->applyStatusFilter($query, $entity, $request->status);
        }

        // Apply search filter
        if ($request->search) {
            $this->applySearchFilter($query, $entity, $request->search);
        }

        $data = $query->get();

        $filename = $this->generateFilename($entity, $request->format);
        $filepath = storage_path('exports/' . $filename);

        // Create exports directory if not exists
        if (!is_dir(storage_path('exports'))) {
            mkdir(storage_path('exports'), 0755, true);
        }

        try {
            switch ($request->format) {
                case 'csv':
                    $this->exportToCsv($data, $filepath, $entity, $request->columns);
                    break;
                case 'xlsx':
                    $this->exportToXlsx($data, $filepath, $entity, $request->columns);
                    break;
                case 'pdf':
                    $this->exportToPdf($data, $filepath, $entity, $request->columns);
                    break;
                case 'json':
                    $this->exportToJson($data, $filepath, $entity, $request->columns);
                    break;
                case 'word':
                    $this->exportToWord($data, $filepath, $entity, $request->columns);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Data exported successfully',
                'filename' => $filename,
                'download_url' => route('admin.export.download', $filename),
                'file_size' => $this->formatFileSize(filesize($filepath)),
                'record_count' => $data->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download exported file
     */
    public function downloadExport($filename)
    {
        $filepath = storage_path('exports/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Get export templates
     */
    public function getExportTemplates($entity)
    {
        $templates = [
            'basic' => [
                'name' => 'Basic Export',
                'columns' => $this->getBasicColumns($entity),
                'description' => 'Basic information for quick export',
            ],
            'detailed' => [
                'name' => 'Detailed Export',
                'columns' => $this->getDetailedColumns($entity),
                'description' => 'Complete information with all fields',
            ],
            'summary' => [
                'name' => 'Summary Export',
                'columns' => $this->getSummaryColumns($entity),
                'description' => 'Summary statistics and key metrics',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Export using template
     */
    public function exportUsingTemplate(Request $request, $entity)
    {
        $validator = Validator::make($request->all(), [
            'template' => 'required|in:basic,detailed,summary',
            'format' => 'required|in:csv,xlsx,pdf',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $columns = $this->getTemplateColumns($entity, $request->template);
        $request->merge(['columns' => $columns]);

        return $this->exportEntity($request, $entity);
    }

    /**
     * Get export history
     */
    public function getExportHistory()
    {
        $exports = [];
        $exportDir = storage_path('exports');

        if (is_dir($exportDir)) {
            $files = scandir($exportDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filepath = $exportDir . '/' . $file;
                    $exports[] = [
                        'filename' => $file,
                        'size' => $this->formatFileSize(filesize($filepath)),
                        'created_at' => date('Y-m-d H:i:s', filemtime($filepath)),
                        'download_url' => route('admin.export.download', $file),
                    ];
                }
            }
        }

        // Sort by creation date (newest first)
        usort($exports, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return response()->json([
            'success' => true,
            'data' => $exports,
        ]);
    }

    /**
     * Delete export file
     */
    public function deleteExport($filename)
    {
        $filepath = storage_path('exports/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        unlink($filepath);

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully',
        ]);
    }

    // Helper methods
    private function getEntityQuery($entity)
    {
        switch ($entity) {
            case 'users':
                return User::query()->with(['roles', 'permissions']);
            case 'courses':
                return Course::query()->with(['instructor', 'users']);
            case 'subscriptions':
                return Subscription::query()->with(['user', 'plan']);
            case 'marketplace':
                return AuraProduct::query()->with(['vendor', 'category']);
            case 'orders':
                return AuraOrder::query()->with(['user', 'items']);
            case 'pagebuilder':
                return AuraPage::query()->with(['author']);
            case 'certificates':
                return Certificate::query()->with(['user', 'template']);
            case 'stuff':
                return Stuff::query()->with(['vendor', 'category']);
            default:
                throw new \Exception("Unknown entity: {$entity}");
        }
    }

    private function applyFilter($query, $entity, $key, $value)
    {
        switch ($key) {
            case 'role':
                if ($entity === 'users') {
                    $query->whereHas('roles', function ($q) use ($value) {
                        $q->where('slug', $value);
                    });
                }
                break;
            case 'category':
                if ($entity === 'marketplace') {
                    $query->where('category_id', $value);
                } elseif ($entity === 'stuff') {
                    $query->where('category_id', $value);
                }
                break;
            case 'vendor':
                if ($entity === 'marketplace') {
                    $query->where('vendor_id', $value);
                } elseif ($entity === 'stuff') {
                    $query->where('vendor_id', $value);
                }
                break;
            case 'type':
                if ($entity === 'stuff') {
                    $query->where('type', $value);
                }
                break;
        }
    }

    private function applyStatusFilter($query, $entity, $status)
    {
        switch ($entity) {
            case 'users':
                $query->where('status', $status);
                break;
            case 'courses':
            case 'stuff':
                $query->where('status', $status);
                break;
            case 'subscriptions':
                $query->where('status', $status);
                break;
            case 'orders':
                $query->where('status', $status);
                break;
            case 'pagebuilder':
                $query->where('status', $status);
                break;
            case 'certificates':
                $query->where('status', $status);
                break;
        }
    }

    private function applySearchFilter($query, $entity, $search)
    {
        switch ($entity) {
            case 'users':
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
                });
                break;
            case 'courses':
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
                break;
            case 'stuff':
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhere('tags', 'like', '%' . $search . '%');
                });
                break;
            case 'marketplace':
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
                break;
        }
    }

    private function generateFilename($entity, $format)
    {
        return $entity . '_export_' . date('Y-m-d_H-i-s') . '.' . $format;
    }

    private function exportToCsv($data, $filepath, $entity, $columns = null)
    {
        $file = fopen($filepath, 'w');
        
        // UTF-8 BOM for proper encoding
        fwrite($file, "\xEF\xBB\xBF");
        
        // Header
        $headers = $columns ?: $this->getBasicColumns($entity);
        fputcsv($file, $headers);
        
        // Data
        foreach ($data as $item) {
            $row = [];
            foreach ($headers as $header) {
                $row[] = $this->getExportValue($item, $header, $entity);
            }
            fputcsv($file, $row);
        }
        
        fclose($file);
    }

    private function exportToXlsx($data, $filepath, $entity, $columns = null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = $columns ?: $this->getBasicColumns($entity);
        
        // Header row
        $sheet->fromArray([$headers]);
        
        // Style header row
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFD700']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
        
        // Data rows
        $rowIndex = 2;
        foreach ($data as $item) {
            $row = [];
            foreach ($headers as $header) {
                $row[] = $this->getExportValue($item, $header, $entity);
            }
            $sheet->fromArray([$row], 'A' . $rowIndex);
            $rowIndex++;
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
    }

    private function exportToPdf($data, $filepath, $entity, $columns = null)
    {
        $pdf = new Dompdf();
        $pdf->setPaper('a4', 'landscape');
        
        $headers = $columns ?: $this->getBasicColumns($entity);
        
        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, strtoupper($entity) . ' Export Report', 0, 1, 'C', 0, 0, 0, true, 'C');
        $pdf->Ln(10);
        
        // Date and count
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'));
        $pdf->Cell(0, 6, 'Total Records: ' . $data->count(), 0, 0, 'R', 0, 0, 0, true, 'R');
        $pdf->Ln(10);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetTextColor(255);
        $cellWidth = 190 / count($headers);
        
        foreach ($headers as $header) {
            $pdf->Cell($cellWidth, 8, $header, 1, 0, 'C', true);
        }
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Ln(8);
        
        // Data rows
        $pdf->SetFont('helvetica', '', 10);
        foreach ($data as $item) {
            foreach ($headers as $header) {
                $pdf->Cell($cellWidth, 6, $this->getExportValue($item, $header, $entity), 1, 0, 'C', true);
            }
            $pdf->Ln(6);
        }
        
        $pdf->Output($filepath, 'F');
    }

    private function exportToJson($data, $filepath, $entity, $columns = null)
    {
        $exportData = [];
        
        foreach ($data as $item) {
            $row = [];
            $headers = $columns ?: $this->getBasicColumns($entity);
            
            foreach ($headers as $header) {
                $row[$header] = $this->getExportValue($item, $header, $entity);
            }
            
            $exportData[] = $row;
        }
        
        file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function exportToWord($data, $filepath, $entity, $columns = null)
    {
        $content = "<!DOCTYPE html><html><head><meta charset='utf-8'>";
        $content .= "<title>" . strtoupper($entity) . " Export Report</title>";
        $content .= "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .meta { color: #666; margin-bottom: 20px; }
        </style></head><body>";
        
        $content .= "<h1>" . strtoupper($entity) . " Export Report</h1>";
        $content .= "<div class='meta'>";
        $content .= "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";
        $content .= "<p>Total Records: " . $data->count() . "</p>";
        $content .= "</div>";
        
        $headers = $columns ?: $this->getBasicColumns($entity);
        
        $content .= "<table>";
        $content .= "<thead><tr>";
        foreach ($headers as $header) {
            $content .= "<th>" . htmlspecialchars($header) . "</th>";
        }
        $content .= "</tr></thead><tbody>";
        
        foreach ($data as $item) {
            $content .= "<tr>";
            foreach ($headers as $header) {
                $content .= "<td>" . htmlspecialchars($this->getExportValue($item, $header, $entity)) . "</td>";
            }
            $content .= "</tr>";
        }
        
        $content .= "</tbody></table>";
        $content .= "</body></html>";
        
        file_put_contents($filepath, $content);
    }

    private function getExportValue($item, $column, $entity)
    {
        switch ($column) {
            case 'id':
                return $item->id;
            case 'name':
                return $item->name ?? $item->title ?? 'N/A';
            case 'email':
                return $item->email ?? 'N/A';
            case 'status':
                return $item->status ?? 'N/A';
            case 'created_at':
                return $item->created_at->format('Y-m-d H:i:s');
            case 'updated_at':
                return $item->updated_at?->format('Y-m-d H:i:s');
            case 'price':
                return $item->price ? '$' . number_format($item->price, 2) : 'N/A';
            case 'total_amount':
                return $item->total_amount ? '$' . number_format($item->total_amount, 2) : 'N/A';
            case 'role':
                return $item->role ?? $item->roles->pluck('name')->first() ?? 'N/A';
            case 'description':
                return Str::limit(strip_tags($item->description ?? ''), 100);
            case 'phone':
                return $item->phone ?? 'N/A';
            case 'country':
                return $item->country ?? 'N/A';
            default:
                return $item->{$column} ?? 'N/A';
        }
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    private function getBasicColumns($entity)
    {
        switch ($entity) {
            case 'users':
                return ['id', 'name', 'email', 'role', 'status', 'created_at'];
            case 'courses':
                return ['id', 'title', 'instructor_name', 'status', 'price', 'created_at'];
            case 'subscriptions':
                return ['id', 'user_name', 'plan_name', 'status', 'price', 'created_at'];
            case 'marketplace':
                return ['id', 'name', 'vendor_name', 'category', 'price', 'status', 'created_at'];
            case 'orders':
                return ['id', 'order_number', 'user_name', 'total_amount', 'status', 'created_at'];
            case 'pagebuilder':
                return ['id', 'title', 'author_name', 'status', 'view_count', 'created_at'];
            case 'certificates':
                return ['id', 'user_name', 'template_name', 'status', 'issued_at', 'created_at'];
            case 'stuff':
                return ['id', 'name', 'type', 'price', 'status', 'created_at'];
            default:
                return ['id', 'name', 'status', 'created_at'];
        }
    }

    private function getDetailedColumns($entity)
    {
        switch ($entity) {
            case 'users':
                return ['id', 'name', 'email', 'phone', 'country', 'role', 'status', 'email_verified_at', 'last_login_at', 'created_at', 'updated_at'];
            case 'courses':
                return ['id', 'title', 'description', 'instructor_name', 'category', 'level', 'price', 'status', 'featured', 'published_at', 'created_at', 'updated_at'];
            case 'subscriptions':
                return ['id', 'user_name', 'plan_name', 'status', 'price', 'billing_cycle', 'auto_renew', 'expires_at', 'canceled_at', 'created_at', 'updated_at'];
            case 'marketplace':
                return ['id', 'name', 'description', 'vendor_name', 'category', 'type', 'price', 'sale_price', 'stock_quantity', 'status', 'featured', 'created_at', 'updated_at'];
            case 'orders':
                return ['id', 'order_number', 'user_name', 'total_amount', 'subtotal', 'tax_amount', 'discount_amount', 'status', 'payment_status', 'created_at', 'updated_at'];
            case 'pagebuilder':
                return ['id', 'title', 'content', 'author_name', 'slug', 'status', 'is_homepage', 'view_count', 'published_at', 'created_at', 'updated_at'];
            case 'certificates':
                return ['id', 'user_name', 'template_name', 'certificate_number', 'verification_code', 'status', 'issued_at', 'expires_at', 'created_at', 'updated_at'];
            case 'stuff':
                return ['id', 'name', 'description', 'type', 'vendor_name', 'category', 'price', 'sale_price', 'license_type', 'status', 'created_at', 'updated_at'];
            default:
                return $this->getBasicColumns($entity);
        }
    }

    private function getSummaryColumns($entity)
    {
        switch ($entity) {
            case 'users':
                return ['id', 'name', 'email', 'role', 'status', 'courses_count', 'last_login_at'];
            case 'courses':
                return ['id', 'title', 'instructor_name', 'status', 'price', 'enrollments_count', 'rating', 'created_at'];
            case 'subscriptions':
                return ['id', 'user_name', 'plan_name', 'status', 'price', 'billing_cycle', 'revenue', 'created_at'];
            case 'marketplace':
                return ['id', 'name', 'vendor_name', 'category', 'price', 'sales_count', 'revenue', 'status', 'created_at'];
            case 'orders':
                return ['id', 'order_number', 'user_name', 'total_amount', 'status', 'payment_status', 'created_at'];
            case 'pagebuilder':
                return ['id', 'title', 'author_name', 'status', 'view_count', 'created_at'];
            case 'certificates':
                return ['id', 'user_name', 'template_name', 'status', 'issued_at', 'created_at'];
            case 'stuff':
                return ['id', 'name', 'type', 'vendor_name', 'price', 'sales_count', 'revenue', 'status', 'created_at'];
            default:
                return $this->getBasicColumns($entity);
        }
    }

    private function getTemplateColumns($entity, $template)
    {
        switch ($template) {
            case 'basic':
                return $this->getBasicColumns($entity);
            case 'detailed':
                return $this->getDetailedColumns($entity);
            case 'summary':
                return $this->getSummaryColumns($entity);
            default:
                return $this->getBasicColumns($entity);
        }
    }
}

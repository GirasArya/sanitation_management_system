<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TaskSubmissionModel;
use OpenApi\Attributes as OA;

class Verifikator extends BaseController
{
    private ?bool $hasRevisionImageColumn = null;
    private ?bool $hasUniqueCodeColumn = null;

    private function canUseUniqueCodeColumn(): bool
    {
        if ($this->hasUniqueCodeColumn !== null) {
            return $this->hasUniqueCodeColumn;
        }

        try {
            $db = \Config\Database::connect();
            $this->hasUniqueCodeColumn = $db->fieldExists('unique_code', 'r_task_submission');
        } catch (\Throwable $e) {
            $this->hasUniqueCodeColumn = false;
        }

        return $this->hasUniqueCodeColumn;
    }

    private function canUseRevisionImageColumn(): bool
    {
        if ($this->hasRevisionImageColumn !== null) {
            return $this->hasRevisionImageColumn;
        }

        try {
            $db = \Config\Database::connect();
            $this->hasRevisionImageColumn = $db->fieldExists('revision_image_path', 'r_task_submission');
        } catch (\Throwable $e) {
            $this->hasRevisionImageColumn = false;
        }

        return $this->hasRevisionImageColumn;
    }

    public function index()
    {
        // check JWT session to retrieve info
        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }
        $decode = $this->jwt->decode(session()->get('jwt'));
        if (time() > $decode['expire_time'] || $decode['user_role'] != 'verifikator') {
            return redirect()->to('auth/login');
        }

        $sent_data = [
            'page_title' => 'Verifikator Page',
            'user_info' => $this->jwt->decode(session()->get('jwt')),
        ];

        return view('verifikator/vw_rekapitulasi', $sent_data);
    }

    #[OA\Post(
        path: '/verifikator/get_datatable',
        summary: 'Task submission DataTable',
        description: 'Paginated, filtered task submission list for the verifikator view.',
        tags: ['Verifikator'],
        security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'draw',        type: 'integer'),
                        new OA\Property(property: 'start',       type: 'integer'),
                        new OA\Property(property: 'length',      type: 'integer'),
                        new OA\Property(property: 'location_id', type: 'string', example: '0'),
                        new OA\Property(property: 'date',        type: 'string', example: '0'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'DataTable JSON',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'data', type: 'object', items: new OA\Items(ref: '#/components/schemas/TaskSubmission'))
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No Active Session',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 401),
                        new OA\Property(property: 'error', type: 'string', example: 'No active session')
                    ]
                )
            )
        ]
    )]
    public function get_datatable()
    {
        $taskSubmissionModel = new TaskSubmissionModel();

        if (!session()->has('jwt')) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 401,
                'error' => 'No active session'
            ]);
        }

        $data = [
            'draw' => (int) ($this->request->getPost('draw') ?? 0),
            'start' => (int) ($this->request->getPost('start') ?? 0),
            'length' => (int) ($this->request->getPost('length') ?? 10),
            'search' => $this->request->getPost('search')['value'] ?? '',
            'location_id' => $this->request->getPost('location_id') ?? '0',
            'date' => $this->request->getPost('date') ?? '0',
            'order_column' => '',
            'order_sort' => ''
        ];

        // Handle ordering
        $order = $this->request->getVar('order');
        if ($order && is_array($order) && isset($order[0])) {
            $columns = [
                2 => 'rts.date',
                3 => 'mi.item_name',
                4 => 'ma.action_name',
                5 => 'ml.location_name',
                6 => 'rts.status'
            ];
            $columnIndex = $order[0]['column'];
            $columnSortOrder = $order[0]['dir'];

            if (isset($columns[$columnIndex])) {
                $data['order_column'] = $columns[$columnIndex];
                $data['order_sort'] = $columnSortOrder;
            }
        }

        $result = $taskSubmissionModel->getSubmittedTasks($data);

        return $this->response->setJSON([
            "status" => 200,
            "data" => $result
        ]);
    }

    #[OA\Post(
        path: '/verifikator/get_locations',
        summary: 'Get submitted locations for a given date',
        tags: ['Verifikator'],
        security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'date',        type: 'string', example: '0'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Array of locations', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', type: 'object', items: new OA\Items(ref: '#/components/schemas/Location'))
                ]
            ))
        ]
    )]
    public function get_locations()
    {
        $taskSubmissionModel = new TaskSubmissionModel();
        $date = $this->request->getPost('date') ?? '0';
        $locations = $taskSubmissionModel->getSubmittedLocations($date);

        return $this->response->setJSON([
            'success' => true,
            'data' => $locations,
        ]);
    }

    #[OA\Post(
        path: '/verifikator/get_dates',
        summary: 'Get submitted dates for a given location',
        tags: ['Verifikator'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'location_id', type: 'string', example: '0'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Array of dates', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', type: 'object', items: new OA\Items(type: 'string'))
                ]
            ))
        ]
    )]
    public function get_dates()
    {
        $taskSubmissionModel = new TaskSubmissionModel();
        $location_id = $this->request->getPost('location_id') ?? '0';
        $dates = $taskSubmissionModel->getSubmittedDates($location_id);

        return $this->response->setJSON([
            'success' => true,
            'data' => $dates,
        ]);
    }

    // #[OA\Get(
    //     path: '/verifikator/get_submitted_task',
    //     summary: 'Get submitted task',
    //     tags: ['Verifikator'],
    //     security: [['sessionAuth' => []]],
    //     responses: [
    //         new OA\Response(response: 200, description: 'Submitted task data')
    //     ]
    // )]
    // public function get_submitted_task()
    // {
    //     // return $this->
    // }

    public function rekapitulasi()
    {
        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }
        $decode = $this->jwt->decode(session()->get('jwt'));
        if (time() > $decode['expire_time'] || $decode['user_role'] != 'verifikator') {
            return redirect()->to('auth/login');
        }

        $sent_data = [
            'page_title' => 'Rekapitulasi',
            'user_info' => $decode,
        ];

        return view('verifikator/vw_dashboard', $sent_data);
    }

    #[OA\Get(
        path: '/verifikator/laporan/rekapitulasi/summary',
        summary: 'Get rekapitulasi summary counts',
        description: 'Returns counts of pending, revisi, and verified submissions.',
        tags: ['Verifikator'],
        security: [['sessionAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Summary counts', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean'),
                    new OA\Property(property: 'data',    type: 'object', properties: [
                        new OA\Property(property: 'pending',  type: 'integer'),
                        new OA\Property(property: 'revisi',   type: 'integer'),
                        new OA\Property(property: 'verified', type: 'integer'),
                    ])
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')),
        ]
    )]
    public function get_rekapitulasi_summary()
    {
        if (!session()->has('jwt')) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
        }

        $decode = $this->jwt->decode(session()->get('jwt'));
        if (time() > $decode['expire_time'] || $decode['user_role'] != 'verifikator') {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
        }

        $taskSubmissionModel = new TaskSubmissionModel();
        $rows = $taskSubmissionModel->builder('r_task_submission')
            ->select('LOWER(status) AS status, COUNT(*) AS total', false)
            ->groupBy('LOWER(status)')
            ->get()
            ->getResultArray();

        $summary = [
            'pending' => 0,
            'revisi' => 0,
            'verified' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $total = (int) ($row['total'] ?? 0);

            if ($status === 'pending') {
                $summary['pending'] += $total;
                continue;
            }

            if (in_array($status, ['revisi', 'revised', 'revise'], true)) {
                $summary['revisi'] += $total;
                continue;
            }

            if (in_array($status, ['verified', 'selesai'], true)) {
                $summary['verified'] += $total;
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $summary,
        ]);
    }

    #[OA\Get(
        path: '/verifikator/export',
        summary: 'Export task submissions',
        description: 'Download filtered task submissions as Excel (default) or PDF.',
        tags: ['Verifikator'],
        security: [['sessionAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'format',      in: 'query', schema: new OA\Schema(type: 'string',  enum: ['excel', 'pdf']), example: 'excel'),
            new OA\Parameter(name: 'location_id', in: 'query', schema: new OA\Schema(type: 'string'),  example: '0'),
            new OA\Parameter(name: 'date',        in: 'query', schema: new OA\Schema(type: 'string'),  example: '0'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File download (xlsx or html/pdf)'),
            new OA\Response(response: 302, description: 'Redirect to login if unauthenticated'),
        ]
    )]
    public function export_filtered()
    {
        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }
        $decode = $this->jwt->decode(session()->get('jwt'));
        if (time() > $decode['expire_time'] || $decode['user_role'] !== 'verifikator') {
            return redirect()->to('auth/login');
        }

        $format     = $this->request->getGet('format') ?? 'excel';
        $locationId = $this->request->getGet('location_id') ?? '0';
        $date       = $this->request->getGet('date') ?? '0';

        $taskSubmissionModel = new TaskSubmissionModel();
        $result = $taskSubmissionModel->getSubmittedTasks([
            'location_id' => $locationId,
            'date'        => $date,
            'search'      => '',
        ]);
        $rows = $result['data'] ?? [];

        // Resolve location label
        $locationLabel = 'Semua Lokasi';
        if ($locationId !== '0') {
            $db  = \Config\Database::connect();
            $loc = $db->table('m_locations')->select('location_name')->where('location_id', (int) $locationId)->get()->getRowArray();
            if ($loc) {
                $locationLabel = $loc['location_name'];
            }
        }

        $dateLabel = $date !== '0' ? $date : 'Semua Tanggal';

        if ($format === 'excel') {
            return $this->_exportExcel($rows, $locationLabel, $dateLabel);
        }

        return $this->_exportPdf($rows, $locationLabel, $dateLabel);
    }

    private function _exportExcel(array $rows, string $locationLabel, string $dateLabel)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Tugas');

        // Title
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Daftar Tugas');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Subtitle
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Lokasi: ' . $locationLabel . '   |   Tanggal: ' . $dateLabel . '   |   Dibuat: ' . date('d/m/Y H:i'));
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Header row
        $headers = ['#', 'Kode', 'Tanggal', 'Item', 'Aksi', 'Lokasi', 'Status', 'Waktu Dibersihkan'];
        $col     = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF3B5998');
            $sheet->getStyle($col . '3')->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        // Data rows
        $rowNum = 4;
        foreach ($rows as $i => $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            $statusLabel = match ($status) {
                'verified', 'selesai' => 'Terverifikasi',
                'pending'             => 'Menunggu',
                'revisi', 'revised', 'revise' => 'Revisi',
                'resubmitted'         => 'Dikirim Ulang',
                default               => $row['status'] ?? '-',
            };

            $sheet->setCellValue('A' . $rowNum, $i + 1);
            $sheet->setCellValue('B' . $rowNum, $row['unique_code'] ?? '-');
            $sheet->setCellValue('C' . $rowNum, $row['date'] ?? '-');
            $sheet->setCellValue('D' . $rowNum, $row['item_name'] ?? '-');
            $sheet->setCellValue('E' . $rowNum, $row['action_name'] ?? '-');
            $sheet->setCellValue('F' . $rowNum, $row['location_name'] ?? '-');
            $sheet->setCellValue('G' . $rowNum, $statusLabel);
            $sheet->setCellValue('H' . $rowNum, $row['time_cleaned'] ?? '-');
            $rowNum++;
        }

        // Auto-size
        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'daftar-tugas-' . date('Ymd-His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    private function _exportPdf(array $rows, string $locationLabel, string $dateLabel)
    {
        $tableRows = '';
        foreach ($rows as $i => $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            $statusLabel = match ($status) {
                'verified', 'selesai' => 'Terverifikasi',
                'pending'             => 'Menunggu',
                'revisi', 'revised', 'revise' => 'Revisi',
                'resubmitted'         => 'Dikirim Ulang',
                default               => $row['status'] ?? '-',
            };

            $no          = $i + 1;
            $code        = htmlspecialchars($row['unique_code'] ?? '-', ENT_QUOTES, 'UTF-8');
            $date        = htmlspecialchars($row['date'] ?? '-', ENT_QUOTES, 'UTF-8');
            $item        = htmlspecialchars($row['item_name'] ?? '-', ENT_QUOTES, 'UTF-8');
            $action      = htmlspecialchars($row['action_name'] ?? '-', ENT_QUOTES, 'UTF-8');
            $location    = htmlspecialchars($row['location_name'] ?? '-', ENT_QUOTES, 'UTF-8');
            $statusEsc   = htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');
            $timeCleaned = htmlspecialchars($row['time_cleaned'] ?? '-', ENT_QUOTES, 'UTF-8');

            $tableRows .= "<tr>
                <td>{$no}</td>
                <td>{$code}</td>
                <td>{$date}</td>
                <td>{$item}</td>
                <td>{$action}</td>
                <td>{$location}</td>
                <td>{$statusEsc}</td>
                <td>{$timeCleaned}</td>
            </tr>";
        }

        $locEsc  = htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8');
        $dateEsc = htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8');
        $gen     = date('d/m/Y H:i:s');
        $count   = count($rows);

        $html = <<<HTML

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tugas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; padding: 20px; color: #111; }
        h2 { text-align: center; font-size: 16px; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #555; font-size: 10px; margin-bottom: 6px; }
        .meta { text-align: center; color: #777; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #3b5998; color: #fff; font-size: 11px; }
        tr:nth-child(even) { background: #f5f5f5; }
        .no-print { margin-bottom: 14px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 18px;background:#3b5998;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;">
            &#128438; Print / Save as PDF
        </button>
    </div>
    <h2>Daftar Tugas</h2>
    <p class="subtitle">Lokasi: {$locEsc} &nbsp;&bull;&nbsp; Tanggal: {$dateEsc}</p>
    <p class="meta">Dibuat: {$gen} &nbsp;&bull;&nbsp; Total: {$count} data</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Item</th>
                <th>Aksi</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Waktu Dibersihkan</th>
            </tr>
        </thead>
        <tbody>
            {$tableRows}
        </tbody>
    </table>
    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
HTML;

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setBody($html);
    }

    #[OA\Post(
        path: '/verifikator/verify_all',
        summary: 'Verify all pending tasks',
        description: 'Marks all pending submissions matching current filters as verified.',
        tags: ['Verifikator'],
        security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'location_id', type: 'string', example: '0'),
                    new OA\Property(property: 'date',        type: 'string', example: '0'),
                    new OA\Property(property: 'search',      type: 'string', example: ''),
                ])
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification count',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Verified 5 data'),
                        new OA\Property(property: 'updated_count', type: 'integer', example: 5),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 401),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
                    ]
                )
            ),
        ]
    )]
    public function verify_all()
    {
        $jwt = session()->get('jwt');
        if (!$jwt) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $user = $this->jwt->decode($jwt);
        if (time() > $user['expire_time'] || ($user['user_role'] ?? '') !== 'verifikator') {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $filters = [
            'location_id' => $this->request->getPost('location_id') ?? '0',
            'date' => $this->request->getPost('date') ?? '0',
            'search' => trim((string) ($this->request->getPost('search') ?? '')),
        ];

        $taskSubmissionModel = new TaskSubmissionModel();
        $updatedCount = $taskSubmissionModel->verifyPendingTasks($filters, isset($user['user_id']) ? (int) $user['user_id'] : null);

        if ($updatedCount === 0) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tidak ada tugas pending yang dapat diverifikasi.',
                'updated_count' => 0,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => $updatedCount . ' tugas pending berhasil diverifikasi.',
            'updated_count' => $updatedCount,
        ]);
    }

    #[OA\Post(
        path: '/verifikator/modal',
        summary: 'Get task detail for modal',
        description: 'Returns full task submission details including location, item, actions, and revision info.',
        tags: ['Verifikator'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(required: ['id'], properties: [new OA\Property(property: 'id', type: 'integer')])
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task detail JSON',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'data', type: 'object', items: new OA\Items(ref: '#/components/schemas/TaskSubmission')),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Empty Task ID',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 422),
                        new OA\Property(property: 'message', type: 'string', example: 'Task ID is required'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Task not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 404),
                        new OA\Property(property: 'message', type: 'string', example: 'Task not found'),
                    ]
                )
            ),
        ]
    )]
    public function modal()
    {
        $id = $this->request->getPost('id');
        if (!$id) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 422,
                'message' => 'Task id is required',
            ]);
        }

        $taskSubmissionModel = new TaskSubmissionModel();
        $task = $taskSubmissionModel->find($id);

        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'Task not found',
            ]);
        }

        // Get full details with joins
        $db = \Config\Database::connect();
        $hasUniqueCode = $this->canUseUniqueCodeColumn();
        $actionNamesAggregate = str_contains(strtolower($db->DBDriver ?? ''), 'postgre')
            ? "STRING_AGG(DISTINCT ma.action_name, ', ' ORDER BY ma.action_name) AS action_names"
            : 'GROUP_CONCAT(DISTINCT ma.action_name ORDER BY ma.action_name SEPARATOR ", ") AS action_names';

        $uniqueCodeSelect = $hasUniqueCode ? 'rts.unique_code' : "'' AS unique_code";

        $baseSelect = '
                rts.task_submission_id,
                rts.date,
                rts.location_id,
                rts.item_id,
                rts.status,
            ' . $uniqueCodeSelect . ',
                rts.revision_message,
                ml.location_name,
                mi.item_name,
                ' . $actionNamesAggregate;

        if ($this->canUseRevisionImageColumn()) {
            $baseSelect = '
                rts.task_submission_id,
                rts.date,
                rts.location_id,
                rts.item_id,
                rts.status,
                ' . $uniqueCodeSelect . ',
                rts.revision_message,
                rts.revision_image_path,
                ml.location_name,
                mi.item_name,
                ' . $actionNamesAggregate;
        }

        $groupByParts = [
            'rts.task_submission_id',
            'rts.date',
            'rts.location_id',
            'rts.item_id',
            'rts.status',
            'rts.revision_message',
            'ml.location_name',
            'mi.item_name',
        ];

        if ($hasUniqueCode) {
            $groupByParts[] = 'rts.unique_code';
        }

        if ($this->canUseRevisionImageColumn()) {
            $groupByParts[] = 'rts.revision_image_path';
        }

        $groupBy = implode(', ', $groupByParts);

        $taskDetails = $taskSubmissionModel->builder('r_task_submission AS rts')
            ->select($baseSelect, false)
            ->join('m_locations AS ml', 'ml.location_id = rts.location_id', 'left')
            ->join('m_items AS mi', 'mi.item_id = rts.item_id', 'left')
            ->join('r_task_submission_detail AS rtsd', 'rts.task_submission_id = rtsd.task_submission_id', 'left')
            ->join('m_actions AS ma', 'ma.action_id = rtsd.action_id', 'left')
            ->where('rts.task_submission_id', $id)
            ->groupBy($groupBy)
            ->get()
            ->getRowArray();

        if (!$taskDetails) {
            return $this->response->setJSON(['success' => false, 'message' => 'Task details not found']);
        }

        if (!$this->canUseRevisionImageColumn()) {
            $taskDetails['revision_image_path'] = null;
        } elseif (empty($taskDetails['revision_image_path']) && !empty($task['revision_image_path'])) {
            // Fallback from base task row in case aggregation query omits the value.
            $taskDetails['revision_image_path'] = $task['revision_image_path'];
        }

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'success' => true,
            'data' => $taskDetails
        ]);
    }

    #[OA\Post(
        path: '/verifikator/update',
        summary: 'Verify or request revision on a task',
        description: 'Set task status to `verified` or `revised`. For revision, optionally attach an image.',
        tags: ['Verifikator'],
        security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id', 'action'],
                    properties: [
                        new OA\Property(property: 'id',                 type: 'integer'),
                        new OA\Property(property: 'action',             type: 'string', enum: ['verifikasi', 'revisi']),
                        new OA\Property(property: 'revise_description', type: 'string'),
                        new OA\Property(property: 'revise_image',       type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status updated'),
            new OA\Response(response: 400, description: 'Invalid action or image'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 500, description: 'Database error'),
        ]
    )]
    public function update()
    {
        $id = $this->request->getPost('id');
        $action = $this->request->getPost('action'); // 'verifikasi' or 'revisi'
        $revise_description = $this->request->getPost('revise_description') ?? '';

        $jwt = session()->get('jwt');
        if (!$jwt) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $user = $this->jwt->decode($jwt);

        $taskSubmissionModel = new TaskSubmissionModel();
        $task = $taskSubmissionModel->find($id);

        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Task not found']);
        }

        if ($action === 'verifikasi') {
            $data = [
                'status' => 'verified',
                'verified_by' => $user['user_id'] ?? null,
                'verified_at' => date('Y-m-d H:i:s')
            ];
        } elseif ($action === 'revisi') {
            $revisionImagePath = $task['revision_image_path'] ?? null;
            $imageFile = $this->request->getFile('revise_image');

            $canUseRevisionImage = $this->canUseRevisionImageColumn();

            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                if (!$canUseRevisionImage) {
                    // Gracefully continue update without image when schema is not ready.
                    $imageFile = null;
                }
            }

            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower((string) $imageFile->getExtension());

                if (!in_array($ext, $allowedExt, true)) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'message' => 'Format gambar tidak didukung. Gunakan jpg, jpeg, png, atau webp.',
                    ]);
                }

                if ($imageFile->getSizeByUnit('mb') > 5) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'message' => 'Ukuran gambar maksimal 5 MB.',
                    ]);
                }

                $uploadDir = FCPATH . 'uploads/revisions';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = $imageFile->getRandomName();
                $imageFile->move($uploadDir, $newFileName);
                $revisionImagePath = 'uploads/revisions/' . $newFileName;

                if (!empty($task['revision_image_path'])) {
                    $relativePath = ltrim((string) $task['revision_image_path'], '/\\');
                    $oldPaths = [
                        WRITEPATH . $relativePath,
                        FCPATH . $relativePath,
                    ];

                    foreach ($oldPaths as $oldPath) {
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                            break;
                        }
                    }
                }
            }

            $data = [
                // Mark as revised so downstream views can render the "Revisi" badge
                'status' => 'revised',
                'revision_message' => $revise_description,
                'verified_by' => null,
                'verified_at' => null
            ];

            if ($canUseRevisionImage) {
                $data['revision_image_path'] = $revisionImagePath;
            }
        } else {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid action']);
        }

        $updated = $taskSubmissionModel->update($id, $data);

        if ($updated) {
            return $this->response->setJSON(['success' => true, 'message' => 'Status updated successfully']);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to update status']);
    }
}

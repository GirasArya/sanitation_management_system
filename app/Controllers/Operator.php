<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ActionModel;
use App\Models\ItemModel;
use App\Models\LocationModel;
use App\Models\TaskSubmissionModel;
use App\Models\TaskSubmissionDetailModel;
use OpenApi\Attributes as OA;

class Operator extends BaseController
{
    private ?bool $hasRevisionImageColumn = null;

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

    /**
     * Display operator dashboard with available rooms
     */
    public function index()
    {
        $location = new LocationModel();
        $taskSubmission = new TaskSubmissionModel();
        $db = \Config\Database::connect();
        $rooms = $location->getAllRoom();
        $today = date('Y-m-d');
        $hasUniqueCodeColumn = false;

        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }

        try {
            $hasUniqueCodeColumn = $db->fieldExists('unique_code', 'r_task_submission');
        } catch (\Throwable $e) {
            $hasUniqueCodeColumn = false;
        }

        // Preload submission counts and revision locations for today to avoid per-room queries
        $submitCountSelect = $hasUniqueCodeColumn
            ? 'rts.location_id, COUNT(DISTINCT rts.unique_code) AS submit_count'
            : 'rts.location_id, COUNT(DISTINCT rts.task_submission_id) AS submit_count';

        $submittedCounts = $db->table('r_task_submission AS rts')
            ->select($submitCountSelect, false)
            ->where('rts.date', $today)
            ->groupBy('rts.location_id')
            ->get()
            ->getResultArray();

        $submittedCountsByLocation = [];
        foreach ($submittedCounts as $row) {
            $submittedCountsByLocation[(int) $row['location_id']] = (int) ($row['submit_count'] ?? 0);
        }

        $revisionLocationIds = $taskSubmission->select('location_id')
            ->where('date', $today)
            ->whereIn('status', ['revisi', 'revised'])
            ->groupBy('location_id')
            ->findColumn('location_id') ?? [];

        // Mark rooms that already have submissions today and those with revisions
        foreach ($rooms as &$room) {
            $roomId = $room['location_id'];
            $room['submit_count'] = $submittedCountsByLocation[(int) $roomId] ?? 0;
            $room['submitted'] = $room['submit_count'] > 0;
            $room['has_revision'] = in_array($roomId, $revisionLocationIds, true);
        }

        return view('operator/vw_dashboard', [
            'page_title' => 'Dashboard User',
            'user_info' => $this->jwt->decode(session()->get('jwt')),
            'rooms' => $rooms
        ]);
    }

    /**
     * Display QR scan page with items for a specific location
     */
    public function scan($location_id = null)
    {
        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }

        if (!$location_id) {
            return redirect()->to('operator')->with('error', 'Location tidak ditemukan');
        }

        $itemModel = new ItemModel();
        $locationModel = new LocationModel();
        $taskSubmissionModel = new TaskSubmissionModel();
        $actionModel = new ActionModel();

        $items = $itemModel->getRoomItem($location_id);
        $locations = $locationModel->find($location_id);

        if (!$locations) {
            return redirect()->to('operator')->with('error', 'Lokasi tidak ditemukan');
        }

        $today = date('Y-m-d');
        $submissions = $taskSubmissionModel->getSubmissionsWithDetails($location_id, $today);

        // Fetch items with revision status (revisi/revised)
        $revisionItemIds = $taskSubmissionModel->builder('r_task_submission AS rts')
            ->select('rts.item_id')
            ->where('rts.location_id', $location_id)
            ->where('rts.date', $today)
            ->whereIn('rts.status', ['revisi', 'revised'])
            ->groupBy('rts.item_id')
            ->get()
            ->getResultArray();
        $revisionItemIdList = array_column($revisionItemIds, 'item_id');

        return view('operator/vw_qr_scan', [
            'page_title' => 'Scan Page',
            'user_info' => $this->jwt->decode(session()->get('jwt')),
            'items' => $items,
            'locations' => $locations,
            'actions' => $actionModel->findAll(),
            'submissions' => $submissions,
            'revision_items' => $revisionItemIdList
        ]);
    }

    /**
     * Return modal content based on type
     */
    public function modal()
    {
        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }

        $id = $this->request->getVar('id');
        $location_id = $this->request->getVar('location_id');
        $type = $this->request->getVar('type');

        if ($type === 'detail') {
            $actionModel = new ActionModel();
            $actions = $actionModel->where('item_id', $id)->findAll();

            // Fetch default clean action from m_actions with item_id=999 and action_name='sudah bersih'
            $cleanActionId = null;
            $defaultActionModel = new ActionModel();
            $defaultClean = $defaultActionModel
                ->where('item_id', 999)
                ->where('action_name', 'sudah bersih')
                ->first();

            if ($defaultClean && isset($defaultClean['action_id'])) {
                $cleanActionId = (int) $defaultClean['action_id'];
            }

            return $this->response->setJSON([
                'status' => 200,
                'title' => 'List Aksi',
                'html' => view('operator/modal/vw_modal_list_action', [
                    'actions' => $actions,
                    'item_id' => $id,
                    'location_id' => $location_id,
                    'clean_action_id' => $cleanActionId
                ])
            ]);
        }

        return $this->response->setStatusCode(404)->setJSON([
            'status' => 404,
            'message' => 'Modal tidak ditemukan'
        ]);
    }

    /**
     * Save task submission with error handling and validation
     */
    #[OA\Post(
        path: '/operator/add',
        summary: 'Submit cleaning task(s)',
        description: 'Saves one or more cleaning task submissions for a given location. Groups submissions by item and generates a unique batch code.',
        tags: ['Operator'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['user_id', 'submissions'],
                    properties: [
                        new OA\Property(property: 'user_id', type: 'integer', example: 2),
                        new OA\Property(
                            property: 'submissions',
                            type: 'string',
                            description: 'JSON array of submission objects',
                            example: '[{"location_id":1,"item_id":3,"action_id":5,"date":"2026-06-17"}]'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Submissions saved', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'unique_code', type: 'string')
                ]
            )),
            new OA\Response(response: 400, description: 'Invalid input', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer'),
                    new OA\Property(property: 'message', type: 'string')
                ]
            )),
            new OA\Response(response: 401, description: 'No active session', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer'),
                    new OA\Property(property: 'error', type: 'string')
                ]
            )),
        ]
    )]
    public function add_submission()
    {
        $user_id = $this->request->getVar('user_id');
        $submissions_json = $this->request->getVar('submissions');

        if (!session()->has('jwt')) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error' => 'No active session'
            ]);
        }

        if (!$user_id) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'User ID tidak valid']);
        }

        $taskSubmissionModel = new TaskSubmissionModel();
        $taskSubmissionDetailModel = new TaskSubmissionDetailModel();

        if ($submissions_json) {
            $submissions = json_decode($submissions_json, true);

            if (!$submissions || empty($submissions)) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'Data submission tidak valid']);
            }

            $location_id = $submissions[0]['location_id'];
            $date = $submissions[0]['date'];

            // Generate ONE unique code for this entire submission batch.
            // All items submitted in this call share the same code.
            // Items in the same location+time are treated as a single submit.
            $batchUserId = (int) $user_id;
            $seq = $taskSubmissionModel->getNextSubmitSeq($batchUserId, $date);
            $dateYmd = str_replace('-', '', $date);
            $uniqueCode = '#' . $batchUserId . '-' . $dateYmd . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);

            // Group new submissions by item
            $grouped = [];
            foreach ($submissions as $submission) {
                $itemId = (int) $submission['item_id'];
                if (!isset($grouped[$itemId])) {
                    $grouped[$itemId] = [
                        'date' => $submission['date'],
                        'location_id' => (int) $submission['location_id'],
                        'item_id' => $itemId,
                        'submitted_by' => (int) $user_id,
                        'actions' => []
                    ];
                }

                $aid = $submission['action_id'];
                if (is_numeric($aid)) {
                    $grouped[$itemId]['actions'][] = (int) $aid;
                }
            }

            // Process submissions: every save creates a new task submission row.
            // Older revised rows for the same room/date/item are marked as resubmitted.
            foreach ($grouped as $itemId => $payload) {
                // Fetch old revision images before overwriting status so we can delete them
                if ($this->canUseRevisionImageColumn()) {
                    $oldRows = $taskSubmissionModel->builder()
                        ->select('revision_image_path')
                        ->where('location_id', $payload['location_id'])
                        ->where('item_id', $payload['item_id'])
                        ->where('date', $payload['date'])
                        ->whereIn('status', ['revisi', 'revised'])
                        ->get()
                        ->getResultArray();

                    foreach ($oldRows as $oldRow) {
                        if (!empty($oldRow['revision_image_path'])) {
                            $filePath = FCPATH . ltrim((string) $oldRow['revision_image_path'], '/\\');
                            if (is_file($filePath)) {
                                @unlink($filePath);
                            }
                        }
                    }
                }

                $resubmitData = ['status' => 'resubmitted'];
                if ($this->canUseRevisionImageColumn()) {
                    $resubmitData['revision_image_path'] = null;
                }

                $taskSubmissionModel->builder()
                    ->where('location_id', $payload['location_id'])
                    ->where('item_id', $payload['item_id'])
                    ->where('date', $payload['date'])
                    ->whereIn('status', ['revisi', 'revised'])
                    ->update($resubmitData);

                $data = [
                    'date' => $payload['date'],
                    'location_id' => $payload['location_id'],
                    'item_id' => $payload['item_id'],
                    'time_cleaned' => 1,
                    'unique_code' => $uniqueCode,
                    'revision_message' => null,
                    'status' => 'pending',
                    'submitted_by' => $payload['submitted_by'],
                    'verified_by' => null,
                    'verified_at' => null
                ];

                // Filter out columns that don't exist in the database
                $data = $taskSubmissionModel->filterDataForInsert($data);
                $taskId = $taskSubmissionModel->insert($data);

                foreach ($payload['actions'] as $actionId) {
                    $taskSubmissionDetailModel->insert([
                        'task_submission_id' => $taskId,
                        'action_id' => $actionId,
                        'quantity' => 1
                    ]);
                }
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Submission berhasil disimpan',
                'unique_code' => $uniqueCode,
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'Data submission tidak ditemukan']);
    }

    /**
     * Increment location visit count
     */
    #[OA\Post(
        path: '/operator/increment_visit/{location_id}',
        summary: 'Record a room visit',
        tags: ['Operator'],
        security: [['sessionAuth' => []]],
        parameters: [new OA\Parameter(name: 'location_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Visit recorded', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: "success"),
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Unauthorized')
                ]
            )),
            new OA\Response(response: 400, description: 'Unauthorized', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Invalid user')
                ]
            )),
        ]
    )]
    public function increment_visit($location_id)
    {
        $jwt = session()->get('jwt');

        if (!$jwt) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $user = $this->jwt->decode($jwt);
        if (!isset($user['user_id'])) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid user']);
        }

        // Visit tracking no longer writes to r_task_submission to avoid schema mismatches
        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Cancel task submission
     */
    #[OA\Delete(
        path: '/operator/cancel/{action_id}',
        summary: 'Cancel a task submission',
        tags: ['Operator'],
        security: [['sessionAuth' => []]],
        parameters: [new OA\Parameter(name: 'action_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Submission cancelled', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'Submission cancelled successfully'),
                ]
            )),
            new OA\Response(response: 404, description: 'Not Found', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Invalid action_id')
                ]
            )),
            new OA\Response(response: 401, description: 'No active session', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'No Active Session')
                ]
            )),
        ]
    )]
    public function cancel_submission($action_id)
    {
        if (!session()->has('jwt')) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error' => 'No active session'
            ]);
        }

        if (!$action_id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid action_id']);
        }

        $taskSubmissionDetailModel = new TaskSubmissionDetailModel();
        $detail = $taskSubmissionDetailModel->where('action_id', $action_id)->first();
        if ($detail) {
            $taskSubmissionModel = new TaskSubmissionModel();
            $taskSubmissionDetailModel->delete($detail['task_submission_detail_id']);
            $taskSubmissionModel->delete($detail['task_submission_id']);
            return $this->response->setStatusCode(200)->setJSON(['status' => 'success', 'message' => 'Submission cancelled successfully']);
        }
        return $this->response->setStatusCode(404)->setJSON(['error' => 'Invalid action_id']);
    }

    /**
     * Display revision tasks for operator
     */
    public function revisi()
    {
        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }

        $user = $this->jwt->decode(session()->get('jwt'));

        // Get submissions that need revision with optimized query
        $db = \Config\Database::connect();
        $actionNamesAggregate = str_contains(strtolower($db->DBDriver ?? ''), 'postgre')
            ? "STRING_AGG(DISTINCT ma.action_name, ', ' ORDER BY ma.action_name) AS action_names"
            : 'GROUP_CONCAT(DISTINCT ma.action_name ORDER BY ma.action_name SEPARATOR ", ") AS action_names';

        $hasRevisionImage = $this->canUseRevisionImageColumn();
        $revisionImageSelect = $hasRevisionImage ? 'rts.revision_image_path,' : "'' AS revision_image_path,";
        $revisionImageGroupBy = $hasRevisionImage ? ', rts.revision_image_path' : '';

        $revisedSubmissions = $db->table('r_task_submission AS rts')
            ->select(
                '
                rts.task_submission_id,
                rts.date,
                rts.location_id,
                rts.item_id,
                rts.revision_message,
                ' . $revisionImageSelect . '
                rts.status,
                rts.submitted_by,
                ml.location_name,
                mi.item_name,
                ' . $actionNamesAggregate,
                false
            )
            ->join('r_task_submission_detail AS rtsd', 'rts.task_submission_id = rtsd.task_submission_id')
            ->join('m_actions AS ma', 'ma.action_id = rtsd.action_id', 'left')
            ->join('m_locations AS ml', 'ml.location_id = rts.location_id', 'left')
            ->join('m_items AS mi', 'mi.item_id = rts.item_id', 'left')
            ->whereIn('rts.status', ['revisi', 'revised'])
            ->groupBy('rts.task_submission_id, rts.date, rts.location_id, rts.item_id, rts.revision_message' . $revisionImageGroupBy . ', rts.status, rts.submitted_by, ml.location_name, mi.item_name')
            ->orderBy('rts.date', 'DESC')
            ->get()
            ->getResultArray();

        // Count unique locations needing revision
        $revisionRoomCount = count(array_unique(array_column($revisedSubmissions, 'location_id')));

        return view('operator/vw_revisi', [
            'page_title' => 'Revisi Tugas',
            'user_info' => $user,
            'submissions' => $revisedSubmissions,
            'items' => (new ItemModel())->findAll(),
            'actions' => (new ActionModel())->findAll(),
            'locations' => (new LocationModel())->findAll(),
            'revision_room_count' => $revisionRoomCount
        ]);
    }
}

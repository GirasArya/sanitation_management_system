<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LocationModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;


class Dashboard extends BaseController
{
    // View
    public function index(): RedirectResponse|string
    {
        // Check user info
        if (session()->has('jwt')) {
            $decode = $this->jwt->decode(session()->get('jwt'));
            if (time() > $decode['expire_time'] || $decode['user_role'] != 'administrator') {
                return redirect()->to('auth/login');
            }
        } else {
            return redirect()->to('auth/login');
        }

        // Prepare data to send to the view
        $sent_data = [
            'page_title' => 'Administrator Page',
            'user_info' => $this->jwt->decode(session()->get('jwt')),
        ];

        // Load the view with the data
        return view('admin/vw_dashboard_admin', $sent_data);
    }

    #[OA\Get(
        path: '/admin/get_stats',
        summary: 'Get admin statistics',
        description: 'Returns statistics about users in the system',
        tags: ['Admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', example: [
                            new OA\Property(property: 'total_users', type: 'integer', example: 10),
                            new OA\Property(property: 'admin_count', type: 'integer', example: 1),
                            new OA\Property(property: 'operator_count', type: 'integer', example: 5),
                            new OA\Property(property: 'verifikator_count', type: 'integer', example: 4),
                        ]),
                    ]
                )
            )
        ]
    )]
    public function get_stats(): ResponseInterface
    {
        $userModel = new UserModel();
        $data = [
            'total_users' => $userModel->countAllResults(),
            'admin_count' => $userModel->where('user_role', 'administrator')->countAllResults(),
            'operator_count' => $userModel->where('user_role', 'operator')->countAllResults(),
            'verifikator_count' => $userModel->where('user_role', 'verifikator')->countAllResults(),
        ];

        return $this->response->setJSON([
            'status' => 200,
            'message' => "Success",
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: '/admin/get_room_visits',
        summary: 'Get room visits',
        description: 'Returns statistics about room visits in the system',
        tags: ['Admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', example: [
                            new OA\Property(property: 'location_name', type: 'string', example: 'Room 1'),
                            new OA\Property(property: 'visit_count', type: 'integer', example: 10),
                        ]),
                    ]
                )
            )
        ]
    )]
    public function get_room_visits(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $hasUniqueCodeColumn = false;

        try {
            $hasUniqueCodeColumn = $db->fieldExists('unique_code', 'r_task_submission');
        } catch (\Throwable $e) {
            $hasUniqueCodeColumn = false;
        }

        $visitCountSelect = $hasUniqueCodeColumn
            ? 'COUNT(DISTINCT rts.unique_code) AS visit_count'
            : 'COUNT(DISTINCT rts.task_submission_id) AS visit_count';

        $date = trim((string) ($this->request->getVar('date') ?? ''));
        $hasValidDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;

        $builder = $db->table('m_locations AS ml')
            ->select('ml.location_name, ' . $visitCountSelect)
            ->join('r_task_submission AS rts', 'rts.location_id = ml.location_id')
            ->groupBy('ml.location_id, ml.location_name')
            ->orderBy('visit_count', 'DESC');

        if ($hasUniqueCodeColumn) {
            $builder->where('rts.unique_code IS NOT NULL', null, false)
                ->where("rts.unique_code != ''", null, false);
        }

        if ($hasValidDate) {
            $builder->where('rts.date', $date);
        }

        $result = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 200,
            'message' => "Success",
            'data' => $result
        ]);
    }

    #[OA\Get(
        path: '/admin/get_locations',
        summary: 'Get location',
        description: 'Returns location available',
        tags: ['Admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', example: [
                            new OA\Property(property: 'location_id', type: 'integer', example: 1),
                            new OA\Property(property: 'location_name', type: 'string', example: 'Office Lantai 1'),
                        ]),
                    ]
                )
            )
        ]
    )]
    public function get_locations(): ResponseInterface
    {
        $locationModel = new LocationModel();

        return $this->response->setJSON([
            'status' => 200,
            'message' => "Success",
            'data'   => $locationModel->getAllRoom(),
        ]);
    }

    #[OA\Get(
        path: '/admin/get_item_clean_count',
        summary: 'Get item clean count',
        description: 'Returns item clean count',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(
                name: 'location_id',
                in: 'query',
                description: 'Location ID',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', example: [
                            new OA\Property(property: 'item_name', type: 'string', example: 'Meja'),
                            new OA\Property(property: 'clean_count', type: 'string', example: '0'),
                        ]),
                    ]
                )
            )
        ]
    )]
    public function get_item_clean_count(): ResponseInterface
    {
        $locationId = (int) $this->request->getVar('location_id');
        $date = trim((string) ($this->request->getVar('date') ?? ''));
        $hasValidDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;

        if ($locationId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 400,
                'message' => 'location_id required',
            ]);
        }

        $db = \Config\Database::connect();

        $cleanCountSelect = 'COUNT(rts.task_submission_id) AS clean_count';
        if ($hasValidDate) {
            $escapedDate = $db->escape($date);
            $cleanCountSelect = "SUM(CASE WHEN rts.date = {$escapedDate} THEN 1 ELSE 0 END) AS clean_count";
        }

        $result = $db->table('m_items AS mi')
            ->select('mi.item_name, ' . $cleanCountSelect, false)
            ->join('r_task_submission AS rts', 'rts.item_id = mi.item_id', 'left')
            ->where('mi.location_id', $locationId)
            ->groupBy('mi.item_id, mi.item_name')
            ->orderBy('mi.item_name', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Success',
            'data'   => $result,
        ]);
    }
}

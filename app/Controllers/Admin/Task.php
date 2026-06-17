<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActionModel;
use App\Models\ItemModel;
use App\Models\LocationModel;
use OpenApi\Attributes as OA;

class Task extends BaseController
{
    /**
     * Helper method to build datatable response
     */
    private function _getDatatableResponse($model, $method, $param)
    {
        $result = $model->$method($param);
        return [
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $result['data'],
            'draw' => $result['draw'],
        ];
    }

    /**
     * Helper method to get datatable parameters from request
     */
    private function _getDatatableParam($additionalParams = [])
    {
        $index = $this->request->getPost("order[0][column]") ?? 1;
        return array_merge([
            'start' => $this->request->getPost("start"),
            'length' => $this->request->getPost("length"),
            'order_column' => $this->request->getPost("columns[$index][data]") ?? 1,
            'order_sort' => $this->request->getPost("order[0][dir]") ?? "ASC",
            'search' => $this->request->getPost("search[value]"),
            'draw' => $this->request->getPost("draw"),
        ], $additionalParams);
    }

    public function index(?int $location_id = null, ?int $item_id = null)
    {
        // check JWT session to retrieve info
        if (!session()->has('jwt')) {
            return redirect()->to('auth/login');
        }
        $decode = $this->jwt->decode(session()->get('jwt'));
        if (time() > $decode['expire_time'] || $decode['user_role'] != 'administrator') {
            return redirect()->to('auth/login');
        }

        if ($location_id != null && $item_id != null) {
            $locationModel = new LocationModel();
            $itemModel = new ItemModel();
            $location_name = $locationModel->find($location_id)['location_name'];
            $item_name = $itemModel->find($item_id)['item_name'];
            $sent_data = [
                'page_title' => 'Manajemen Aksi',
                'user_info' => $this->jwt->decode(session()->get('jwt')),
                'location_name' => $location_name,
                'item_name' => $item_name,
                'item_id' => $item_id
            ];

            return view('admin/vw_manage_task_action', $sent_data);
        }

        if ($location_id != null) {
            $locationModel = new LocationModel();
            $location_name = $locationModel->find($location_id)['location_name'];
            $sent_data = [
                'page_title' => 'Manajemen Item',
                'user_info' => $this->jwt->decode(session()->get('jwt')),
                'location_name' => $location_name,
                'location_id' => $location_id
            ];

            return view('admin/vw_manage_task_item', $sent_data);
        }

        $sent_data = [
            'page_title' => 'Manajemen Lokasi',
            'user_info' => $this->jwt->decode(session()->get('jwt')),
        ];

        return view('admin/vw_manage_task_location', $sent_data);
    }

    #[OA\Post(
        path: '/admin/manage/task/get_datatable/location',
        summary: 'Location DataTable data',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\MediaType(mediaType: 'application/x-www-form-urlencoded', schema: new OA\Schema(properties: [new OA\Property(property: 'draw', type: 'integer'), new OA\Property(property: 'start', type: 'integer'), new OA\Property(property: 'length', type: 'integer')]))),
        responses: [new OA\Response(response: 200, description: 'DataTable JSON', content: new OA\JsonContent(ref: '#/components/schemas/DatatableResponse'))]
    )]
    public function get_datatable_location()
    {
        $locationModel = new LocationModel();
        $param = $this->_getDatatableParam();
        $result = $this->_getDatatableResponse($locationModel, 'get_datatable_location', $param);
        return $this->response->setJSON($result);
    }

    #[OA\Post(
        path: '/admin/manage/task/get_datatable/item',
        summary: 'Item DataTable data',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'location_id', type: 'integer'),
                    new OA\Property(property: 'draw', type: 'integer'),
                    new OA\Property(property: 'start', type: 'integer'),
                    new OA\Property(property: 'length', type: 'integer')
                ])
            )
        ),
        responses: [new OA\Response(response: 200, description: 'DataTable JSON', content: new OA\JsonContent(ref: '#/components/schemas/DatatableResponse'))]
    )]
    public function get_datatable_item()
    {
        $itemModel = new ItemModel();
        $param = $this->_getDatatableParam(['location_id' => $this->request->getPost("location_id") ?? '']);
        $result = $this->_getDatatableResponse($itemModel, 'get_datatable_item', $param);
        return $this->response->setJSON($result);
    }

    #[OA\Post(
        path: '/admin/manage/task/get_datatable/action',
        summary: 'Action DataTable data',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]], 
        requestBody: new OA\RequestBody(content: new OA\MediaType(
            mediaType: 'application/x-www-form-urlencoded',
            schema: new OA\Schema(properties: [
                new OA\Property(property: 'item_id', type: 'integer'),
                new OA\Property(property: 'draw', type: 'integer'),
                new OA\Property(property: 'start', type: 'integer'),
                new OA\Property(property: 'length', type: 'integer')
            ])
        )),
        responses: [new OA\Response(response: 200, description: 'DataTable JSON', content: new OA\JsonContent(ref: '#/components/schemas/DatatableResponse'))]
    )]
    public function get_datatable_action()
    {
        $actionModel = new ActionModel();
        $param = $this->_getDatatableParam(['item_id' => $this->request->getPost("item_id") ?? '']);
        $result = $this->_getDatatableResponse($actionModel, 'get_datatable_action', $param);
        return $this->response->setJSON($result);
    }

    public function modal_location()
    {
        $id = $this->request->getVar('id');
        $type = $this->request->getVar('type');
        $locationModel = new LocationModel();

        switch ($type) {
            case 'add':
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => "Tambah Lokasi",
                    'html' => view('admin/modals/manage_location/vw_add_location_form')
                ]);
            case 'edit':
                $location = $locationModel->find($id);
                if (!$location) {
                    return $this->response->setStatusCode(404)->setJSON([
                        "status" => 404,
                        "message" => "Lokasi tidak ditemukan."
                    ]);
                }
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => 'Edit Lokasi',
                    'html' => view('admin/modals/manage_location/vw_edit_location_form', ['location' => $location])
                ]);
            case 'delete':
                $location = $locationModel->find($id);
                if (!$location) {
                    return $this->response->setStatusCode(404)->setJSON([
                        "status" => 404,
                        "message" => "Lokasi tidak ditemukan."
                    ]);
                }
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => 'Hapus Lokasi',
                    'html' => view('admin/modals/manage_location/vw_delete_location_form', ['location' => $location])
                ]);
            default:
                return $this->response->setStatusCode(404)->setJSON([
                    "status" => "Failed",
                    "message" => "Modal tidak ditemukan."
                ]);
        }
    }

    public function modal_item()
    {
        $id = $this->request->getVar('id');
        $type = $this->request->getVar('type');
        $itemModel = new ItemModel();

        switch ($type) {
            case 'add':
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => "Tambah Item",
                    'html' => view('admin/modals/manage_item/vw_add_item_form', ['location_id' => $id])
                ]);
            case 'edit':
                $item = $itemModel->find($id);
                if (!$item) {
                    return $this->response->setStatusCode(404)->setJSON([
                        "status" => 404,
                        "message" => "Item tidak ditemukan."
                    ]);
                }
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => 'Edit Item',
                    'html' => view('admin/modals/manage_item/vw_edit_item_form', ['item' => $item])
                ]);
            case 'delete':
                $item = $itemModel->find($id);
                if (!$item) {
                    return $this->response->setStatusCode(404)->setJSON([
                        "status" => 404,
                        "message" => "Item tidak ditemukan."
                    ]);
                }
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => 'Hapus Item',
                    'html' => view('admin/modals/manage_item/vw_delete_item_form', ['item' => $item])
                ]);
            default:
                return $this->response->setStatusCode(404)->setJSON([
                    "status" => "Failed",
                    "message" => "Modal tidak ditemukan."
                ]);
        }
    }

    public function modal_action()
    {
        $id = $this->request->getVar('id');
        $type = $this->request->getVar('type');
        $actionModel = new ActionModel();

        switch ($type) {
            case 'add':
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => "Tambah Aksi",
                    'html' => view('admin/modals/manage_action/vw_add_action_form', ['item_id' => $id])
                ]);
            case 'edit':
                $action = $actionModel->find($id);
                if (!$action) {
                    return $this->response->setStatusCode(404)->setJSON([
                        "status" => 404,
                        "message" => "Action tidak ditemukan."
                    ]);
                }
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => 'Edit Aksi',
                    'html' => view('admin/modals/manage_action/vw_edit_action_form', ['action' => $action])
                ]);
            case 'delete':
                $action = $actionModel->find($id);
                if (!$action) {
                    return $this->response->setStatusCode(404)->setJSON([
                        "status" => 404,
                        "message" => "Aksi tidak ditemukan."
                    ]);
                }
                return $this->response->setJSON([
                    'status' => 200,
                    'title' => 'Hapus Aksi',
                    'html' => view('admin/modals/manage_action/vw_delete_action_form', ['action' => $action])
                ]);
            default:
                return $this->response->setStatusCode(404)->setJSON([
                    "status" => "Failed",
                    "message" => "Modal tidak ditemukan."
                ]);
        }
    }

    #[OA\Post(
        path: '/admin/manage/task/add/location',
        summary: 'Add a new location (room)',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['location'],
                    properties: [new OA\Property(property: 'location', type: 'string', example: 'Ruang Rapat A')]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Location created', content: new OA\JsonContent(ref: '#/components/schemas/CreatedResponse')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function add_location()
    {
        $locationModel = new LocationModel();
        $rules = [
            'location' => 'required|min_length[3]|max_length[100]|is_unique[m_locations.location_name]',
        ];
        $messages = [
            'location' => [
                'is_unique' => 'Nama ruangan sudah ada'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = $this->validator->getValidated();
        $insertData = ['location_name' => $data['location']];

        if ($locationModel->insert($insertData)) {
            return $this->response->setStatusCode(201)->setJSON([
                'status' => 201,
                'message' => 'Lokasi berhasil ditambahkan'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menambahkan lokasi'
        ]);
    }

    #[OA\Post(
        path: '/admin/manage/task/add/item',
        summary: 'Add an item to a location',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['location_id', 'item'],
                    properties: [
                        new OA\Property(property: 'location_id', type: 'integer'),
                        new OA\Property(property: 'item',        type: 'string', example: 'Meja'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Item created', content: new OA\JsonContent(ref: '#/components/schemas/CreatedResponse')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function add_item()
    {
        $itemModel = new ItemModel();
        $rules = [
            'location_id' => 'required',
            'item' => 'required|min_length[3]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = $this->validator->getValidated();

        // Check if item name already exists in this location
        $existingItem = $itemModel
            ->where('location_id', $data['location_id'])
            ->where('item_name', $data['item'])
            ->first();

        if ($existingItem) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Nama item sudah ada di lokasi ini',
                'errors' => ['item' => 'Nama item sudah ada di lokasi ini']
            ]);
        }

        $insertData = [
            'location_id' => $data['location_id'],
            'item_name' => $data['item'],
        ];

        if ($itemModel->insert($insertData)) {
            return $this->response->setStatusCode(201)->setJSON([
                'status' => 201,
                'message' => 'Item berhasil ditambahkan',
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menambahkan item'
        ]);
    }

    #[OA\Post(
        path: '/admin/manage/task/add/action',
        summary: 'Add a cleaning action to an item',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['item_id', 'aksi'],
                    properties: [
                        new OA\Property(property: 'item_id', type: 'integer'),
                        new OA\Property(property: 'aksi',   type: 'string', example: 'Dilap'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Action created', content: new OA\JsonContent(ref: '#/components/schemas/CreatedResponse')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function add_action()
    {
        $actionModel = new ActionModel();
        $rules = [
            'item_id' => 'required',
            'aksi' => 'required|min_length[3]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = $this->validator->getValidated();
        $insertData = [
            'item_id' => $data['item_id'],
            'action_name' => $data['aksi'],
        ];

        if ($actionModel->insert($insertData)) {
            return $this->response->setStatusCode(201)->setJSON([
                'status' => 201,
                'message' => 'Aksi berhasil ditambahkan'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menambahkan aksi'
        ]);
    }

    #[OA\Put(
        path: '/admin/manage/task/edit/location',
        summary: 'Update a location name',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['id', 'location'],
                    properties: [
                        new OA\Property(property: 'id',       type: 'integer'),
                        new OA\Property(property: 'location', type: 'string'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Location updated', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object')
                ]
            )),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'errors', type: 'object')
                ]
            )),
            new OA\Response(response: 500, description: 'Validation error', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer'),
                    new OA\Property(property: 'message', type: 'string'),
                ]
            )),
        ]
    )]
    public function update_location()
    {
        $id = $this->request->getVar('id');
        $locationModel = new LocationModel();
        $rules = [
            'location' => 'required|min_length[3]|max_length[100]|is_unique[m_locations.location_name,location_id,' . $id . ']',
        ];
        $messages = [
            'location' => [
                'is_unique' => 'Nama ruangan sudah ada'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = $this->validator->getValidated();
        $updateData = ['location_name' => $data['location']];

        if ($locationModel->update($id, $updateData)) {
            return $this->response->setStatusCode(200)->setJSON([
                'status' => 200,
                'message' => 'Lokasi berhasil diupdate',
                'data' => $updateData
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal mengupdate lokasi'
        ]);
    }

    #[OA\Put(
        path: '/admin/manage/task/edit/item',
        summary: 'Update an item',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['id', 'location_id', 'item'],
                    properties: [
                        new OA\Property(property: 'id',          type: 'integer'),
                        new OA\Property(property: 'location_id', type: 'integer'),
                        new OA\Property(property: 'item',        type: 'string'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Item updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'errors', type: 'object')
            ])),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
        ]
    )]
    public function update_item()
    {
        $id = $this->request->getVar('id');
        $itemModel = new ItemModel();
        $rules = [
            'location_id' => 'required',
            'item' => 'required|min_length[3]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = $this->validator->getValidated();
        $updateData = [
            'location_id' => $data['location_id'],
            'item_name' => $data['item'],
        ];

        if ($itemModel->update($id, $updateData)) {
            return $this->response->setStatusCode(200)->setJSON([
                'status' => 200,
                'message' => 'Item berhasil diupdate',
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal mengupdate item'
        ]);
    }

    #[OA\Put(
        path: '/admin/manage/task/edit/action',
        summary: 'Update an action',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['id', 'item_id', 'aksi'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'item_id', type: 'integer'),
                        new OA\Property(property: 'aksi', type: 'string'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Action updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'errors', type: 'object')
            ])),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
        ]
    )]
    public function update_action()
    {
        $id = $this->request->getVar('id');
        $actionModel = new ActionModel();
        $rules = [
            'item_id' => 'required',
            'aksi' => 'required|min_length[3]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = $this->validator->getValidated();
        $updateData = [
            'item_id' => $data['item_id'],
            'action_name' => $data['aksi'],
        ];

        if ($actionModel->update($id, $updateData)) {
            return $this->response->setStatusCode(200)->setJSON([
                'status' => 200,
                'message' => 'Aksi berhasil diupdate'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal mengupdate aksi'
        ]);
    }

    #[OA\Delete(
        path: '/admin/manage/task/delete/location',
        summary: 'Delete a location',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(required: ['id'], properties: [new OA\Property(property: 'id', type: 'integer')])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Location deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
        ]
    )]
    public function delete_location()
    {
        $id = $this->request->getVar('id');
        $locationModel = new LocationModel();
        $location = $locationModel->find($id);

        if (!$location) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'Lokasi tidak ditemukan'
            ]);
        }

        if ($locationModel->delete($id)) {
            return $this->response->setStatusCode(200)->setJSON([
                'status' => 200,
                'message' => 'Lokasi berhasil dihapus'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menghapus lokasi'
        ]);
    }

    #[OA\Delete(
        path: '/admin/manage/task/delete/item',
        summary: 'Delete an item',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(required: ['id'], properties: [new OA\Property(property: 'id', type: 'integer')])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Item deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 500, description: 'Not found', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
        ]
    )]
    public function delete_item()
    {
        $id = $this->request->getVar('id');
        $itemModel = new ItemModel();
        $item = $itemModel->find($id);

        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'Item tidak ditemukan'
            ]);
        }

        if ($itemModel->delete($id)) {
            return $this->response->setStatusCode(200)->setJSON([
                'status' => 200,
                'message' => 'Item berhasil dihapus'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menghapus item'
        ]);
    }

    #[OA\Delete(
        path: '/admin/manage/task/delete/action',
        summary: 'Delete an action',
        tags: ['Admin'],
        // security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(required: ['id'], properties: [new OA\Property(property: 'id', type: 'integer')])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Item deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 500, description: 'Not found', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer'),
                new OA\Property(property: 'message', type: 'string'),
            ])),
        ]
    )]
    public function delete_action()
    {
        $id = $this->request->getVar('id');
        $actionModel = new ActionModel();
        $action = $actionModel->find($id);

        if (!$action) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'message' => 'Aksi tidak ditemukan'
            ]);
        }

        if ($actionModel->delete($id)) {
            return $this->response->setStatusCode(200)->setJSON([
                'status' => 200,
                'message' => 'Aksi berhasil dihapus'
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status' => 500,
            'message' => 'Gagal menghapus aksi'
        ]);
    }
}

<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\IncomingRequest;
use OpenApi\Attributes as OA;

/**
 * @property IncomingRequest $request
 */
class Auth extends BaseController
{
    // Returns View - index
    public function index()
    {
        if (session()->has('jwt')) {
            try {
                $jwt = $this->jwt->decode(session()->get('jwt'));
                if (time() <= $jwt['expire_time']) {
                    if ($jwt['user_role'] == 'administrator') {
                        $role = 'admin';
                    } else if ($jwt['user_role'] == 'operator') {
                        $role = 'operator';
                    } else if ($jwt['user_role'] == 'verifikator') {
                        $role = 'verifikator';
                    } else {
                        return redirect()->to('auth/login');
                    }
                    return redirect()->to($role);
                }
            } catch (\Throwable $e) {
                session()->remove(['jwt', 'key']);
            }
        }

        $sent_data = [
            'page_title' => "Login Page",
            "message" => "success"
        ];

        return view('auth/vw_login', $sent_data);
    }

    // Login Handler
    #[OA\Post(
        path: '/auth/login',
        summary: 'Authenticate user',
        description: 'Validates credentials and stores a JWT in the PHP session + cookies. Redirects to the appropriate role dashboard on success.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['username', 'password'],
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'admin'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login Successfull',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil'),
                        new OA\Property(property: 'token', type: 'string', example: 'token JWT'),
                        new OA\Property(property: 'role', type: 'string', example: 'admin'),
                        new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                    ]
                )
            ),
            new OA\Response(response: 302, description: 'Redirect to role dashboard on success'),
            new OA\Response(response: 400, description: 'Validation error — username/password too short'),
        ]
    )]
    public function login_handler()
    {
        if (!$this->request->is('post')) {
            return redirect('auth/login');
        }

        // Input validation
        $validation = service('validation');
        $rules = [
            'username' => [
                'label' => 'Username',
                'rules' => 'required|min_length[3]|max_length[50]',
                'errors' => [
                    'required' => '{field} harus diisi',
                    'min_length' => '{field} minimal 3 karakter',
                    'max_length' => '{field} maksimal 50 karakter'
                ]
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[3]',
                'errors' => [
                    'required' => '{field} harus diisi',
                    'min_length' => '{field} minimal 3 karakter'
                ]
            ]
        ];

        $data = $this->request->is('json')
            ? $this->request->getJSON(true)
            : $this->request->getPost(array_keys($rules));

        // Run Validation
        if (!$this->validateData($data, $rules)) {
            if ($this->request->isAJAX() || $this->request->hasHeader('Accept') && strpos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'errors' => $validation->getErrors()
                ]);
            }
            return redirect()->to('/auth/login')->withInput()->with('errors', $validation->getErrors());
        }

        $validData = $this->validator->getValidated();

        $username = $validData['username'];
        $password = $validData['password'];

        $model = new UserModel();
        $user = $model
            ->select('user_id,name,user_role,password')
            ->where('username', $username)
            ->first();

        if (!$user) {
            // User not found
            return redirect()->to('/auth/login')
                ->withInput()
                ->with('error', 'Username atau password salah');
        }

        [
            'user_id' => $id,
            'name' => $name,
            'user_role' => $role,
            'password' => $db_password
        ] = $user;

        if (!password_verify($password, $db_password)) {
            // Password incorrect
            return redirect()->to('/auth/login')
                ->withInput()
                ->with('error', 'Username atau password salah');
        }

        // Store shift info in session including dates for notification logic
        $data = [
            'user_id' => $id,
            'name' => $name,
            'user_role' => $role,
            'expire_time' => time() + (10 * YEAR)
        ];

        $this->jwt->encode($data);

        $redirectMap = [
            'administrator' => 'admin',
            'operator'      => 'operator',
            'verifikator'   => 'verifikator',
        ];

        $target = $redirectMap[$role] ?? 'auth/login';

        if (strpos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response
                ->setCookie('auth_jwt', (string) session()->get('jwt'), 10 * YEAR, '', '/', '', false, true, 'Lax')
                ->setCookie('auth_key', (string) session()->get('key'), 10 * YEAR, '', '/', '', false, true, 'Lax')
                ->setJSON([
                    'status'  => 'success',
                    'message' => 'Login berhasil',
                    'token'   => session()->get('jwt'),
                    'role'    => $role,
                    'name'    => $name
                ]);
        }
        return redirect()->to($target)
            ->setCookie('auth_jwt', (string) session()->get('jwt'), 10 * YEAR, '', '/', '', false, true, 'Lax')
            ->setCookie('auth_key', (string) session()->get('key'), 10 * YEAR, '', '/', '', false, true, 'Lax');
    }

    #[OA\Get(
        path: '/auth/logout',
        summary: 'Log out',
        description: 'Destroys the PHP session and clears auth cookies. Redirects to login page or returns a JSON message.',
        tags: ['Auth'],
        security: [['sessionAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful (for JSON requests)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout successful')
                    ]
                )
            ),
            new OA\Response(
                response: 302,
                description: 'Redirect to /auth/login (for browser/HTML requests)'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - No active session',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'No active session')
                    ]
                )
            )
        ]
    )]
    public function logout()
    {
        if (!session()->has('jwt')) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'No active session'
            ]);
        }

        session()->destroy();

        if (strpos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response
                ->deleteCookie('auth_jwt')
                ->deleteCookie('auth_key')
                ->setJSON([
                    'status'  => 'success',
                    'message' => 'Logout successful'
                ]);
        }

        return redirect()->to('auth/login')
            ->deleteCookie('auth_jwt')
            ->deleteCookie('auth_key');
    }
}

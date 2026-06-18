<?php

namespace App\Filters;

use App\Libraries\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthMiddleware
 *
 * A before-filter that guards protected routes by verifying an active, non-expired
 * JWT stored in the PHP session.
 *
 * Behaviour:
 *  - No session 'jwt' key          → 401 (API) or redirect to /auth/login (browser)
 *  - JWT decode failure             → 401 (API) or redirect to /auth/login (browser)
 *  - Token expired                  → 401 (API) or redirect to /auth/login (browser)
 *  - Role mismatch (when $arguments → 401 (API) or redirect to /auth/login (browser)
 *    contains allowed roles)
 *  - Valid session                  → null (continue to next filter / controller)
 *
 * Usage in Routes.php:
 *   $routes->group('admin', ['filter' => 'auth:administrator'], static function ($routes) { … });
 *   $routes->group('operator', ['filter' => 'auth:operator'], static function ($routes) { … });
 *   $routes->group('verifikator', ['filter' => 'auth:verifikator'], static function ($routes) { … });
 *   // Without role restriction (any authenticated user):
 *   $routes->group('profile', ['filter' => 'auth'], static function ($routes) { … });
 */
class AuthMiddleware implements FilterInterface
{
    /**
     * Run before every matched request.
     *
     * @param RequestInterface  $request
     * @param array|string|null $arguments  Optional list of allowed roles (e.g. ['administrator'])
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwtToken = session()->get('jwt');

        // ── 1. No session token ──────────────────────────────────────────────
        if (!$jwtToken) {
            return $this->unauthorized($request, 'No active session');
        }

        // ── 2. Decode & validate token ───────────────────────────────────────
        try {
            $jwt     = new JwtService();
            $payload = $jwt->decode($jwtToken);
        } catch (\Throwable $e) {
            // Tampered or unreadable token — purge it
            session()->remove(['jwt', 'key']);

            return $this->unauthorized($request, 'Invalid or malformed token');
        }

        // ── 3. Expiry check ──────────────────────────────────────────────────
        if (!isset($payload['expire_time']) || time() > $payload['expire_time']) {
            session()->remove(['jwt', 'key']);

            return $this->unauthorized($request, 'Session expired');
        }

        // ── 4. Optional role-based restriction ───────────────────────────────
        if (!empty($arguments)) {
            $allowedRoles = is_array($arguments) ? $arguments : explode(',', (string) $arguments);
            $allowedRoles = array_map('trim', $allowedRoles);
            $userRole     = $payload['user_role'] ?? '';

            if (!in_array($userRole, $allowedRoles, true)) {
                return $this->unauthorized($request, 'Forbidden: insufficient role', 403);
            }
        }

        // ── 5. All checks passed — continue ──────────────────────────────────
    }

    /**
     * Run after the controller response — nothing to do here.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return an appropriate response for unauthenticated / unauthorised requests.
     *
     * API / AJAX callers receive a JSON body with the given HTTP status code.
     * Browser requests are redirected to /auth/login.
     *
     * @param RequestInterface $request
     * @param string           $message  Human-readable reason
     * @param int              $code     HTTP status code (401 or 403)
     *
     * @return ResponseInterface|\CodeIgniter\HTTP\RedirectResponse
     */
    private function unauthorized(RequestInterface $request, string $message, int $code = 401)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $isApiRequest = str_contains($acceptHeader, 'application/json');

        if ($isApiRequest) {
            return service('response')
                ->setStatusCode($code)
                ->setJSON([
                    'status'  => $code,
                    'success' => false,
                    'message' => $message,
                ]);
        }

        return redirect()->to(site_url('auth/login'));
    }
}

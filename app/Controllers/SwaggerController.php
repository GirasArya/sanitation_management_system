<?php

namespace App\Controllers;

use OpenApi\Generator;

/**
 * SwaggerController
 *
 * Serves the OpenAPI JSON spec and the Swagger UI page.
 * Routes (public, no auth required):
 *   GET /api/docs        → Swagger UI
 *   GET /api/docs/json   → openapi.json
 */
class SwaggerController extends BaseController
{
    /**
     * Serve the Swagger UI HTML page.
     */
    public function ui(): string
    {
        return view('swagger/vw_swagger_ui');
    }

    /**
     * Generate and serve the OpenAPI JSON specification.
     *
     * In development the spec is generated on every request (live reload).
     * The scan covers all files under app/Controllers and app/Models
     * so that schema annotations are picked up automatically.
     */
    public function json(): \CodeIgniter\HTTP\Response
    {
         $openapi = (new \OpenApi\Generator())->generate([APPPATH . 'Controllers']);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setBody($openapi->toJson());
    }
}

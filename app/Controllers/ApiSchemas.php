<?php

namespace App\Controllers;

use OpenApi\Attributes as OA;

/**
 * This class holds no routing logic — it exists purely to register
 * shared OpenAPI schemas that are referenced by controller annotations.
 *
 * @OA\Info(
 *   title="Sanitation Management System API",
 *   version="1.0.0",
 *   description="API documentation for the Bionic Sanitation Management System (SMS). Authentication is session-based (cookie JWT). Log in via POST /auth/login first."
 * )
 *
 * @OA\Server(url="http://localhost:8080", description="Local Docker")
 *
 * @OA\SecurityScheme(
 *   securityScheme="sessionAuth",
 *   type="apiKey",
 *   in="cookie",
 *   name="auth_jwt",
 *   description="Session JWT stored in the auth_jwt cookie. Obtain by calling POST /auth/login."
 * )
 *
 * @OA\Tag(name="Auth",       description="Authentication endpoints")
 * @OA\Tag(name="Admin",      description="Admin management endpoints (requires administrator role)")
 * @OA\Tag(name="Operator",   description="Operator task submission endpoints (requires operator role)")
 * @OA\Tag(name="Verifikator",description="Verifikator review & export endpoints (requires verifikator role)")
 * @OA\Tag(name="Profile",    description="Profile management — available to all roles")
 */
#[OA\Schema(
    schema: 'SuccessResponse',
    properties: [
        new OA\Property(property: 'status',  type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string',  example: 'Operasi berhasil'),
    ]
)]
#[OA\Schema(
    schema: 'CreatedResponse',
    properties: [
        new OA\Property(property: 'status',  type: 'integer', example: 201),
        new OA\Property(property: 'message', type: 'string',  example: 'Data berhasil ditambahkan'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'status',  type: 'integer', example: 400),
        new OA\Property(property: 'message', type: 'string',  example: 'Validasi gagal'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['field' => 'Field harus diisi']
        ),
    ]
)]
#[OA\Schema(
    schema: 'NotFoundResponse',
    properties: [
        new OA\Property(property: 'status',  type: 'integer', example: 404),
        new OA\Property(property: 'message', type: 'string',  example: 'Data tidak ditemukan'),
    ]
)]
#[OA\Schema(
    schema: 'UnauthorizedResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string',  example: 'Unauthorized'),
    ]
)]
#[OA\Schema(
    schema: 'UserResource',
    properties: [
        new OA\Property(property: 'user_id',   type: 'integer', example: 1),
        new OA\Property(property: 'name',      type: 'string',  example: 'Budi Santoso'),
        new OA\Property(property: 'username',  type: 'string',  example: 'budi'),
        new OA\Property(property: 'user_role', type: 'string',  enum: ['operator', 'verifikator', 'administrator']),
    ]
)]
#[OA\Schema(
    schema: 'Location',
    properties: [
        new OA\Property(property: 'location_id',   type: 'integer', example: 1),
        new OA\Property(property: 'location_name', type: 'string',  example: 'Ruang Rapat A'),
    ]
)]
#[OA\Schema(
    schema: 'Item',
    properties: [
        new OA\Property(property: 'item_id',     type: 'integer', example: 1),
        new OA\Property(property: 'location_id', type: 'integer', example: 1),
        new OA\Property(property: 'item_name',   type: 'string',  example: 'Meja'),
    ]
)]
#[OA\Schema(
    schema: 'Action',
    properties: [
        new OA\Property(property: 'action_id',   type: 'integer', example: 1),
        new OA\Property(property: 'item_id',     type: 'integer', example: 1),
        new OA\Property(property: 'action_name', type: 'string',  example: 'Dilap'),
    ]
)]
#[OA\Schema(
    schema: 'TaskSubmission',
    properties: [
        new OA\Property(property: 'task_submission_id', type: 'integer', example: 42),
        new OA\Property(property: 'date',               type: 'string',  format: 'date', example: '2026-06-17'),
        new OA\Property(property: 'location_id',        type: 'integer', example: 1),
        new OA\Property(property: 'item_id',            type: 'integer', example: 3),
        new OA\Property(property: 'unique_code',        type: 'string',  example: '#1-20260617-001'),
        new OA\Property(property: 'status',             type: 'string',  enum: ['pending', 'verified', 'revisi', 'revised', 'resubmitted']),
        new OA\Property(property: 'revision_message',   type: 'string',  nullable: true),
        new OA\Property(property: 'submitted_by',       type: 'integer', example: 2),
        new OA\Property(property: 'verified_by',        type: 'integer', nullable: true),
        new OA\Property(property: 'verified_at',        type: 'string',  format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'DatatableResponse',
    properties: [
        new OA\Property(property: 'draw',            type: 'integer', example: 1),
        new OA\Property(property: 'recordsTotal',    type: 'integer', example: 50),
        new OA\Property(property: 'recordsFiltered', type: 'integer', example: 10),
        new OA\Property(
            property: 'data',
            type: 'object',
            items: new OA\Items(type: 'object')
        ),
    ]
)]
class ApiSchemas
{
    // Intentionally empty — this class exists only for OpenAPI schema registration.
}

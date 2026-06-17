<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SMS — API Documentation</title>
    <meta name="description" content="Interactive API documentation for the Bionic Sanitation Management System." />
    <link rel="icon" type="image/png" href="<?= base_url('favicon.ico') ?>" />

    <!-- Swagger UI -->
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />

    <style>
        /* ── Base resets ─────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f1117;
            color: #e2e8f0;
        }

        /* ── Top navbar ──────────────────────────────────────────────── */
        #sms-navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 28px;
            height: 58px;
            background: linear-gradient(90deg, #1a1f2e 0%, #1e2433 100%);
            border-bottom: 1px solid rgba(99, 179, 237, 0.15);
            box-shadow: 0 2px 16px rgba(0,0,0,.4);
        }

        #sms-navbar img {
            height: 32px;
            object-fit: contain;
        }

        #sms-navbar .brand {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .02em;
            color: #63b3ed;
        }

        #sms-navbar .badge {
            font-size: .68rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(99,179,237,.15);
            color: #63b3ed;
            border: 1px solid rgba(99,179,237,.3);
            letter-spacing: .04em;
        }

        #sms-navbar .spacer { flex: 1; }

        #sms-navbar .json-link {
            font-size: .8rem;
            color: #90cdf4;
            text-decoration: none;
            padding: 5px 12px;
            border: 1px solid rgba(99,179,237,.3);
            border-radius: 6px;
            transition: background .2s, color .2s;
        }
        #sms-navbar .json-link:hover {
            background: rgba(99,179,237,.12);
            color: #bee3f8;
        }

        /* ── Swagger UI theme overrides ──────────────────────────────── */
        #swagger-ui {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 20px 60px;
        }

        /* Hide built-in topbar (we use our own) */
        .swagger-ui .topbar { display: none !important; }

        /* Dark background for info block */
        .swagger-ui .information-container {
            background: #1a1f2e;
            border-radius: 12px;
            border: 1px solid rgba(99,179,237,.12);
            padding: 24px !important;
            margin-bottom: 24px;
        }

        .swagger-ui .info .title { color: #bee3f8 !important; }
        .swagger-ui .info .description p,
        .swagger-ui .info p { color: #94a3b8 !important; }

        /* Scheme / server selector */
        .swagger-ui .scheme-container {
            background: #1a1f2e !important;
            border-radius: 10px;
            border: 1px solid rgba(99,179,237,.1);
            padding: 16px 24px !important;
            margin-bottom: 24px;
        }

        /* Opblock colours */
        .swagger-ui .opblock.opblock-get    { background: rgba(97,175,254,.08); border-color: rgba(97,175,254,.3); }
        .swagger-ui .opblock.opblock-post   { background: rgba(73,204,144,.08); border-color: rgba(73,204,144,.3); }
        .swagger-ui .opblock.opblock-put    { background: rgba(252,161,48,.08); border-color: rgba(252,161,48,.3); }
        .swagger-ui .opblock.opblock-delete { background: rgba(249,62,62,.08);  border-color: rgba(249,62,62,.3);  }

        .swagger-ui .opblock-tag {
            border-bottom: 1px solid rgba(99,179,237,.1) !important;
            color: #bee3f8 !important;
        }
        .swagger-ui .opblock-tag:hover { background: rgba(99,179,237,.06) !important; }

        .swagger-ui section.models { border: 1px solid rgba(99,179,237,.12) !important; border-radius: 10px; }
        .swagger-ui section.models.is-open h4 { color: #bee3f8 !important; }

        /* Inputs & buttons */
        .swagger-ui .btn.execute { background: #2b6cb0 !important; border-color: #2b6cb0 !important; }
        .swagger-ui .btn.execute:hover { background: #2c5282 !important; }

        .swagger-ui input[type=text],
        .swagger-ui textarea,
        .swagger-ui select {
            background: #1a1f2e !important;
            color: #e2e8f0 !important;
            border-color: rgba(99,179,237,.25) !important;
        }

        /* Response tables */
        .swagger-ui table tbody tr td { color: #94a3b8 !important; }
        .swagger-ui .response-col_status { color: #63b3ed !important; }
    </style>

    <!-- Inter font for polish -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
</head>
<body>

    <!-- ── Custom navbar ─────────────────────────────────────────────── -->
    <nav id="sms-navbar">
        <img src="<?= base_url('logo_dark.png') ?>" alt="Bionic logo" onerror="this.style.display='none'" />
        <span class="brand">Sanitation Management System</span>
        <span class="badge">API v1.0</span>
        <span class="spacer"></span>
        <a class="json-link" href="<?= base_url('api/docs/json') ?>" target="_blank">
            ↓ openapi.json
        </a>
    </nav>

    <!-- ── Swagger UI mount point ────────────────────────────────────── -->
    <div id="swagger-ui"></div>

    <!-- Swagger UI JS -->
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>

    <script>
        window.onload = () => {
            SwaggerUIBundle({
                url: "<?= base_url('api/docs/json') ?>",
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: 'StandaloneLayout',
                deepLinking: true,
                displayRequestDuration: true,
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 2,
                filter: true,
                syntaxHighlight: {
                    activated: true,
                    theme: 'monokai'
                }
            });
        };
    </script>

</body>
</html>

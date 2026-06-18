<?= $this->extend('layouts/vw_auth_layout') ?>

<?= $this->section('page_title') ?>
Login Page
<?= $this->endSection() ?>

<?= $this->section('style') ?>
<style>
    .login-container {
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .logo-section {
        text-align: center;
        margin-bottom: 2rem;
        animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .logo-icon img {
        width: 60px !important;
        height: 60px;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .logo-text h4 {
        color: #1976d2;
        font-weight: 700;
        letter-spacing: 1px;
        margin-top: 1rem;
        font-size: 1.8rem;
    }

    .logo-text p {
        color: #999;
        font-size: 0.9rem;
        margin-top: 0.3rem;
    }

    .form-section {
        margin-top: 2.5rem;
    }

    .form-control {
        border: 2px solid #e0e0e0;
        padding: 12px 16px;
        font-size: 1rem;
        transition: all 0.3s ease;
        border-radius: 8px;
    }

    .form-control:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.15);
        transform: translateY(-2px);
    }

    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.7rem;
        font-size: 0.95rem;
    }

    .btn-sign-in {
        background: white;
        border: 2px solid #1976d2;
        padding: 12px 20px;
        font-weight: 600;
        font-size: 1.05rem;
        color: #1976d2;
        transition: all 0.3s ease;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(25, 118, 210, 0.2);
    }

    .btn-sign-in:hover {
        background: #f5f5f5;
        border-color: #0d47a1;
        color: #0d47a1;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
    }

    .btn-sign-in:active {
        transform: translateY(-1px);
    }

    .alert-danger {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%);
        border: 2px solid #dc3545;
        border-radius: 8px;
        animation: shake 0.5s;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-10px);
        }

        75% {
            transform: translateX(10px);
        }
    }

    .alert-danger strong {
        color: #dc3545;
        font-weight: 600;
    }

    .invalid-feedback {
        display: block !important;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .divider {
        text-align: center;
        margin: 2rem 0;
        position: relative;
        color: #999;
        font-size: 0.9rem;
    }

    .form-helper-text {
        text-align: center;
        margin-top: 1.5rem;
        font-size: 0.9rem;
        color: #666;
    }

    .form-helper-text a {
        color: #1976d2;
        text-decoration: none;
        font-weight: 600;
    }

    .form-helper-text a:hover {
        text-decoration: underline;
    }

    .card {
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
    }

    .card-body {
        padding: 3rem 2.5rem;
    }

    @media (max-width: 576px) {
        .card-body {
            padding: 2rem 1.5rem;
        }

        .logo-text h4 {
            font-size: 1.5rem;
        }

        .btn-sign-in {
            font-size: 1rem;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card-body login-container">
    <!-- Logo Section -->
    <div class="logo-section">
        <a href="<?= base_url('/auth/login'); ?>" class="text-decoration-none">
            <div class="logo-icon mb-3">
                <img src="<?= base_url("logo_dark.png") ?>" alt="Bionic Natura Logo">
            </div>
            <div class="logo-text">
                <h4>BIONIC NATURA</h4>
                <p>Cleaning Tracker</p>
            </div>
        </a>
    </div>

    <!-- Error Alert -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger" role="alert">
            <div class="d-flex align-items-center">
                <iconify-icon icon="zondicons:exclamation-solid" width="24" height="24" class="me-3"></iconify-icon>
                <div>
                    <strong><?= session()->getFlashdata('error') ?></strong>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <div class="form-section">
        <?= form_open('auth/login', ['method' => 'post', 'novalidate' => true]); ?>
        <?= csrf_field(); ?>

        <!-- Username Field -->
        <div class="form-group">
            <label for="username" class="form-label">
                <iconify-icon icon="mdi:account" width="18" height="18" class="me-2"></iconify-icon>Username
            </label>
            <input
                type="text"
                class="form-control <?= (session()->getFlashdata('errors') && isset(session()->getFlashdata('errors')['username'])) ? 'is-invalid' : '' ?>"
                id="username"
                name="username"
                value="<?= old('username') ?>"
                placeholder="Enter your username"
                autocomplete="username"
                autofocus
                required>
            <?php if (session()->getFlashdata('errors') && isset(session()->getFlashdata('errors')['username'])): ?>
                        <div class="invalid-feedback">
                        <?= session()->getFlashdata('errors')['username']; ?>
                    </div>
                    <?php endif; ?>
                    </div>

        <!-- Password Field -->
        <div class="form-group">
            <label for="password" class="form-label">
                <iconify-icon icon="mdi:lock" width="18" height="18" class="me-2"></iconify-icon>Password
            </label>
            <input
                type="password"
                class="form-control <?= (session()->getFlashdata('errors') && isset(session()->getFlashdata('errors')['password'])) ? 'is-invalid' : '' ?>"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required>
            <?php if (session()->getFlashdata('errors') && isset(session()->getFlashdata('errors')['password'])): ?>
                        <div class="invalid-feedback">
                        <?= session()->getFlashdata('errors')['password']; ?>
                    </div>
                    <?php endif; ?>
                    </div>

        <!-- Submit Button -->
        <button class="btn btn-sign-in w-100 py-2 mb-3 rounded-2" type="submit">
            <iconify-icon icon="mdi:login" width="20" height="20" class="me-2"></iconify-icon>Sign In
        </button>

        <?= form_close(); ?>
<!-- Helper Text -->
<div class="form-helper-text">
    <small>© 2026 Bionic Natura. All rights reserved.</small>
</div>
</div>
</div>
<?= $this->endSection() ?>

<?= $this->section('script') ?>
<script>
    // Add ripple effect on button click
    document.querySelectorAll('.btn-sign-in').forEach(button => {
        button.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');

            this.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Add input focus effects
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
        });
    });
</script>
<?= $this->endSection(); ?>
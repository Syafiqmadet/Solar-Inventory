<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>☀️ Solar Inventory — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --solar-orange:#FF6B35; --solar-dark:#1a1a2e; --solar-mid:#16213e; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--solar-dark) 0%, var(--solar-mid) 50%, #0f3460 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, var(--solar-orange), #FFD700);
            padding: 36px 32px 28px;
            text-align: center;
        }
        .login-header .sun { font-size: 3.5rem; margin-bottom: 6px; }
        .login-header h4 { color: #fff; font-weight: 800; margin: 0; font-size: 1.3rem; }
        .login-header p { color: rgba(255,255,255,0.88); margin: 4px 0 0; font-size: 0.85rem; }
        .login-body { padding: 32px; }
        .form-label { font-weight: 600; font-size: 0.85rem; color: #444; }
        .form-control {
            border-radius: 10px; border: 1.5px solid #e0e0e0;
            padding: 10px 14px; font-size: 0.92rem;
        }
        .form-control:focus { border-color: var(--solar-orange); box-shadow: 0 0 0 3px rgba(255,107,53,0.15); }
        .btn-login {
            background: linear-gradient(135deg, var(--solar-orange), #ff8c42);
            color: #fff; border: none; border-radius: 10px;
            padding: 11px; font-weight: 700; font-size: 1rem;
            width: 100%; transition: opacity .2s;
        }
        .btn-login:hover { opacity: 0.9; color: #fff; }
        .input-icon { position: relative; }
        .input-icon i {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: #aaa; font-size: 1rem;
        }
        .input-icon .form-control { padding-left: 38px; }
        .login-footer {
            background: #f8f9fa; padding: 14px 32px;
            text-align: center; border-top: 1px solid #eee;
            font-size: 0.8rem; color: #888;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="sun">☀️</div>
        <h4>Solar Inventory System</h4>
        <p>Sign in to continue</p>
    </div>
    <div class="login-body">

        @if($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 mb-3" style="font-size:.87rem">
            <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
        </div>
        @endif

        @if(session('status'))
        <div class="alert alert-success rounded-3 py-2 px-3 mb-3" style="font-size:.87rem">
            {{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-icon">
                    <i class="bi bi-envelope"></i>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           placeholder="email.com" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-icon" style="position:relative">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" id="passwordField"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="••••••••" required style="padding-right:42px">
                    <button type="button" id="togglePassword"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;padding:0;cursor:pointer;color:#aaa;font-size:1rem;line-height:1"
                            tabindex="-1">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted" for="remember">Remember me</label>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>
    </div>
    <div class="login-footer">
        <i class="bi bi-shield-lock me-1"></i> Protected system — authorised access only
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    var field = document.getElementById('passwordField');
    var icon  = document.getElementById('toggleIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
        this.style.color = '#FF6B35';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
        this.style.color = '#aaa';
    }
});
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Entrar | DICO</title>

    <style>
        :root {
            --primary: #168a5b;
            --primary-dark: #0f6945;
            --primary-soft: #e8f6ef;
            --background: #f4f7f6;
            --surface: #ffffff;
            --text: #17201c;
            --muted: #6c7771;
            --border: #dfe7e3;
            --danger: #c83d4d;
            --shadow: 0 24px 60px rgba(24, 58, 43, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: var(--background);
            color: var(--text);
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
        }

        .login-visual {
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 64px;
            overflow: hidden;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(76, 211, 147, 0.55),
                    transparent 38%
                ),
                linear-gradient(145deg, #06291c, #0d5f3d);
            color: #fff;
        }

        .login-visual::before,
        .login-visual::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        .login-visual::before {
            width: 500px;
            height: 500px;
            top: -180px;
            left: -140px;
        }

        .login-visual::after {
            width: 360px;
            height: 360px;
            right: -120px;
            bottom: -100px;
        }

        .visual-content {
            position: relative;
            z-index: 1;
            max-width: 580px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 48px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #fff;
            color: var(--primary-dark);
            font-size: 18px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.05em;
        }

        .login-visual h1 {
            margin: 0 0 18px;
            max-width: 520px;
            font-size: clamp(38px, 5vw, 64px);
            line-height: 1.04;
            letter-spacing: -0.04em;
        }

        .login-visual p {
            margin: 0;
            max-width: 500px;
            color: rgba(255, 255, 255, 0.76);
            font-size: 18px;
            line-height: 1.7;
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 28px;
            background: var(--surface);
        }

        .login-card {
            width: 100%;
            max-width: 430px;
        }

        .login-card h2 {
            margin: 0 0 10px;
            font-size: 32px;
            letter-spacing: -0.03em;
        }

        .login-subtitle {
            margin: 0 0 34px;
            color: var(--muted);
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        .form-control {
            width: 100%;
            height: 52px;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 0 16px;
            background: #fff;
            color: var(--text);
            font: inherit;
            outline: none;
            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(22, 138, 91, 0.12);
        }

        .form-control.is-invalid {
            border-color: var(--danger);
        }

        .error-message {
            margin-top: 7px;
            color: var(--danger);
            font-size: 13px;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 4px 0 24px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--muted);
            font-size: 14px;
            cursor: pointer;
        }

        .remember-label input {
            accent-color: var(--primary);
        }

        .login-button {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 13px;
            background: var(--primary);
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition:
                transform 0.2s,
                background 0.2s;
        }

        .login-button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            margin-bottom: 22px;
            padding: 13px 15px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-success {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .login-footer {
            margin-top: 28px;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 880px) {
            .login-page {
                display: block;
            }

            .login-visual {
                min-height: 300px;
                padding: 42px 28px;
            }

            .login-visual h1 {
                font-size: 38px;
            }

            .login-visual p {
                font-size: 16px;
            }

            .brand {
                margin-bottom: 30px;
            }

            .login-panel {
                padding-top: 42px;
            }
        }
    </style>
</head>

<body>
    <main class="login-page">
        <section class="login-visual">
            <div class="visual-content">
                <div class="brand">
                    <div class="brand-mark">D</div>
                    <div class="brand-name">DICO SOLUTIONS</div>
                </div>

                <h1>
                    O seu negócio sob controlo.
                </h1>

                <p>
                    Venda, controle o stock, acompanhe as suas lojas e
                    tome decisões com informação actualizada.
                </p>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <h2>Bem-vindo ao DICO</h2>

                <p class="login-subtitle">
                    Introduza os seus dados para aceder ao sistema.
                </p>

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('login.store') }}"
                >
                    @csrf

                    <div class="form-group">
                        <label
                            for="email"
                            class="form-label"
                        >
                            E-mail
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="exemplo@empresa.com"
                            required
                            autofocus
                            autocomplete="email"
                        >

                        @error('email')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label
                            for="password"
                            class="form-label"
                        >
                            Palavra-passe
                        </label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Introduza a palavra-passe"
                            required
                            autocomplete="current-password"
                        >

                        @error('password')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-options">
                        <label class="remember-label">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                            >

                            Manter sessão iniciada
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="login-button"
                    >
                        Entrar no DICO
                    </button>
                </form>

                <div class="login-footer">
                    © {{ date('Y') }} DICO SOLUTIONS
                </div>
            </div>
        </section>
    </main>
</body>
</html>
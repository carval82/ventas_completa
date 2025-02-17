<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LC Desarrollo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 1.5rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background-color: #000;
            color: white;
            padding: 2rem 1rem;
            border-bottom: none;
        }

        .card-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da;
            margin-top: 0.5rem;
        }

        .form-control:focus {
            border-color: #000;
            box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.1);
        }

        .btn-primary {
            background-color: #000;
            border-color: #000;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #333;
            border-color: #333;
            transform: translateY(-2px);
        }

        .login-decoration {
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(45deg, transparent 49%, rgba(0,0,0,0.1) 50%);
            border-radius: 0 0 0 100%;
        }

        label {
            font-weight: 500;
            color: #495057;
        }

        .invalid-feedback {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="login-decoration"></div>
                    <div class="card-header text-center">
                        <img src="/ruta-a-tu-logo.png" alt="LC Desarrollo" class="logo">
                        <h4 class="mb-0">Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-4">
                                <label>Email</label>
                                <input type="email" name="email" 
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="tucorreo@ejemplo.com">
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label>Contraseña</label>
                                <input type="password" name="password" 
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="••••••••">
                                @error('password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Ingresar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
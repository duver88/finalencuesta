<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cargando encuesta...</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            background: #0a0a0a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        /* Efecto difuminado de fondo - Rojo y Negro elegante */
        .background-effects {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .blur-circle {
            position: absolute;
            border-radius: 50%;
            animation: float 15s ease-in-out infinite;
        }

        .blur-circle:nth-child(1) {
            top: -10%;
            left: -5%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(220, 20, 60, 0.25) 0%, transparent 70%);
            filter: blur(80px);
            animation-delay: 0s;
        }

        .blur-circle:nth-child(2) {
            bottom: -15%;
            right: -5%;
            width: 650px;
            height: 650px;
            background: radial-gradient(circle, rgba(139, 0, 0, 0.3) 0%, transparent 70%);
            filter: blur(90px);
            animation-delay: 3s;
        }

        .blur-circle:nth-child(3) {
            top: 30%;
            right: 10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 0, 0, 0.2) 0%, transparent 70%);
            filter: blur(70px);
            animation-delay: 6s;
        }

        .blur-circle:nth-child(4) {
            top: 50%;
            left: 20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(178, 34, 34, 0.18) 0%, transparent 70%);
            filter: blur(65px);
            animation-delay: 9s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }

        .loading-container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(220, 20, 60, 0.3),
                        0 0 80px rgba(255, 0, 0, 0.1);
            border: 2px solid rgba(220, 20, 60, 0.3);
            max-width: 500px;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .spinner-container {
            margin: 2rem auto;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .spinner {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(220, 20, 60, 0.2);
            border-top: 6px solid #DC143C;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #DC143C;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(220, 20, 60, 0.2);
        }

        .loading-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .loading-subtext {
            font-size: 0.95rem;
            color: #999;
        }

        .progress-bar-custom {
            width: 100%;
            height: 4px;
            background: rgba(220, 20, 60, 0.15);
            border-radius: 10px;
            margin-top: 2rem;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #8B0000 0%, #DC143C 50%, #FF0000 100%);
            border-radius: 10px;
            animation: progress 1s ease-in-out;
            width: 0%;
        }

        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Efectos de fondo -->
    <div class="background-effects">
        <div class="blur-circle"></div>
        <div class="blur-circle"></div>
        <div class="blur-circle"></div>
        <div class="blur-circle"></div>
    </div>

    <!-- Contenedor de carga -->
    <div class="loading-container">
        <h1 class="loading-title">
            <i class="bi bi-clipboard-data me-2"></i>
            Cargando Encuesta
        </h1>

        <div class="spinner-container">
            <div class="spinner"></div>
        </div>

        <p class="loading-text">Por favor espera un momento...</p>
        <p class="loading-subtext">Estamos preparando tu encuesta</p>

        <div class="progress-bar-custom">
            <div class="progress-bar-fill"></div>
        </div>
    </div>

    <!-- FingerprintJS -->
    <script src="https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js"></script>

    <script>
        // Generar huella digital del dispositivo primero
        FingerprintJS.load().then(fp => {
            fp.get().then(result => {
                const deviceFingerprint = result.visitorId;

                // Esperar 1 segundo antes de asignar el token
                setTimeout(function() {
                    // Hacer petición AJAX para obtener el token
                    fetch('{{ route("api.assign-token", ["publicSlug" => $publicSlug]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            @if(isset($groupSlug))
                            groupSlug: '{{ $groupSlug }}',
                            @endif
                            deviceFingerprint: deviceFingerprint
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.redirect_url) {
                            // Redirigir a la encuesta con el token asignado
                            window.location.href = data.redirect_url;
                        } else {
                            // Si no hay tokens disponibles, mostrar página de no disponible
                            window.location.href = data.redirect_url || '{{ route("surveys.unavailable") }}';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // En caso de error, redirigir sin token
                        @if(isset($groupSlug))
                        window.location.href = '{{ route("surveys.show.group", ["groupSlug" => $groupSlug, "publicSlug" => $publicSlug]) }}';
                        @else
                        window.location.href = '{{ route("surveys.show", ["publicSlug" => $publicSlug]) }}';
                        @endif
                    });
                }, 1000); // 1 segundo de delay
            });
        });
    </script>
</body>
</html>

@extends('layouts.app')

@section('title', 'Encuesta No Disponible')

@section('content')
<div class="min-vh-100 d-flex align-items-center position-relative" style="background: linear-gradient(135deg, #fff9e6 0%, #e6f2ff 50%, #ffe6e6 100%);">
    <!-- Efecto difuminado de fondo -->
    <div class="position-absolute w-100 h-100" style="overflow: hidden; z-index: 0;">
        <div class="blur-circle" style="position: absolute; top: -10%; left: -5%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255, 209, 0, 0.2) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="blur-circle" style="position: absolute; bottom: -15%; right: -5%; width: 550px; height: 550px; background: radial-gradient(circle, rgba(206, 17, 38, 0.15) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="blur-circle" style="position: absolute; top: 30%; right: 10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(0, 56, 168, 0.15) 0%, transparent 70%); filter: blur(50px);"></div>
    </div>

    <div class="container py-5 position-relative" style="z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card border-0 rounded-4 overflow-hidden" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);">
                    <div class="card-body p-5 text-center">
                        <!-- Icono principal -->
                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 6rem;"></i>
                        </div>

                        <!-- Título -->
                        <h1 class="display-5 fw-bold text-dark mb-3">
                            Encuesta No Disponible
                        </h1>

                        <!-- Descripción -->
                        <p class="lead text-muted mb-4">
                            Esta encuesta ha alcanzado su límite de participantes.
                        </p>

                        <!-- Información adicional -->
                        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning mb-4">
                            <i class="bi bi-info-circle-fill"></i>
                            <strong>No hay cupos disponibles en este momento.</strong>
                        </div>

                        <p class="text-muted mb-4">
                            Te agradecemos tu interés en participar. Lamentablemente, esta encuesta ya no está aceptando nuevas respuestas.
                        </p>

                        <!-- Mensaje de contacto (opcional) -->
                        <div class="mt-5 pt-4 border-top">
                            <p class="text-muted small mb-0">
                                <i class="bi bi-envelope"></i> Si tienes alguna pregunta, por favor contacta al administrador de la encuesta.
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="bi bi-clipboard-data"></i> Sistema de Encuestas
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
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

/* Responsive */
@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
    }

    .lead {
        font-size: 1rem;
    }

    .card-body {
        padding: 2rem 1.5rem !important;
    }

    .bi-exclamation-triangle-fill {
        font-size: 4rem !important;
    }
}
</style>
@endsection

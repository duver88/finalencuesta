@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header del Reporte -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">
                <i class="bi bi-file-earmark-bar-graph text-primary"></i>
                Reporte de Encuesta
            </h1>
            <p class="text-muted mb-0">{{ $survey->title }}</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="{{ route('admin.surveys.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="bi bi-eye fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Vistas Totales</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_tokens']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Votos Válidos</h6>
                            <h3 class="mb-0">{{ number_format($stats['valid_votes']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                <i class="bi bi-exclamation-triangle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Votos No Contados</h6>
                            <h3 class="mb-0">{{ number_format($stats['invalid_votes']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                                <i class="bi bi-clipboard-data fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Votos Totales</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_votes']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-3 p-3">
                                <i class="bi bi-ticket-perforated fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Tokens Usados</h6>
                            <h3 class="mb-0">{{ number_format($stats['used_tokens']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                <i class="bi bi-percent fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Tasa de Conversión</h6>
                            <h3 class="mb-0">{{ $stats['conversion_rate'] }}%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados por Pregunta -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0">
                <i class="bi bi-bar-chart-line text-primary"></i>
                Resultados Detallados por Pregunta
            </h5>
        </div>
        <div class="card-body p-4">
            @foreach($questionStats as $index => $qStat)
                <div class="mb-5 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <!-- Título de la Pregunta -->
                    <div class="mb-4">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-primary me-3" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-grow-1">
                                <h5 class="mb-2">{{ $qStat['question']->question_text }}</h5>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-check-circle"></i>
                                    Total de votos válidos: <strong>{{ number_format($qStat['total_votes']) }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones y Resultados -->
                    <div class="row g-3">
                        @foreach($qStat['options'] as $optStat)
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">{{ $optStat['option']->option_text }}</h6>
                                        <span class="badge bg-primary">{{ number_format($optStat['votes']) }} votos</span>
                                    </div>

                                    <!-- Barra de Progreso -->
                                    <div class="progress mb-2" style="height: 25px;">
                                        <div class="progress-bar bg-primary"
                                             role="progressbar"
                                             style="width: {{ $optStat['percentage'] }}%"
                                             aria-valuenow="{{ $optStat['percentage'] }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <strong>{{ $optStat['percentage'] }}%</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if(count($questionStats) === 0)
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-3">No hay resultados para mostrar</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
@media print {
    .btn, nav, footer {
        display: none !important;
    }

    .card {
        page-break-inside: avoid;
    }

    body {
        font-size: 12pt;
    }
}
</style>
@endsection

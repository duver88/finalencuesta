@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header del Reporte -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">
                <i class="bi bi-file-earmark-bar-graph text-success"></i>
                Reporte Consolidado del Grupo
            </h1>
            <p class="text-muted mb-0">{{ $group->name }}</p>
            <small class="text-muted">
                <i class="bi bi-collection"></i>
                {{ $stats['total_surveys'] }} encuestas en este grupo
            </small>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-success">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="{{ route('admin.survey-groups.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- Información del Grupo -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle fs-4 me-3"></i>
            <div>
                <strong>Reporte Consolidado:</strong> Este reporte suma los resultados de todas las encuestas pertenecientes a este grupo.
                Las preguntas y respuestas mostradas son las mismas en todas las encuestas del grupo.
            </div>
        </div>
    </div>

    <!-- Estadísticas Generales Consolidadas -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                                <i class="bi bi-eye fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Vistas Totales</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_tokens']) }}</h3>
                            <small class="text-muted">En todas las encuestas</small>
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
                            <small class="text-muted">Sumados del grupo</small>
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
                            <small class="text-muted">Duplicados o inválidos</small>
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
                            <small class="text-muted">Todos los intentos</small>
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
                            <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                                <i class="bi bi-files fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Votos Duplicados</h6>
                            <h3 class="mb-0">{{ number_format($stats['duplicate_votes']) }}</h3>
                            <small class="text-muted">Por mismo token</small>
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
                            <small class="text-muted">Total del grupo</small>
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
                                <i class="bi bi-percent fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Tasa de Conversión</h6>
                            <h3 class="mb-0">{{ $stats['conversion_rate'] }}%</h3>
                            <small class="text-muted">Promedio del grupo</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Encuestas del Grupo -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0">
                <i class="bi bi-collection text-success"></i>
                Encuestas Incluidas en Este Reporte
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($group->surveys as $survey)
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $survey->title }}</h6>
                                    <small class="text-muted">
                                        <i class="bi bi-link-45deg"></i>
                                        {{ $survey->public_slug }}
                                    </small>
                                </div>
                                <span class="badge {{ $survey->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $survey->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Resultados Consolidados por Pregunta -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0">
                <i class="bi bi-bar-chart-line text-success"></i>
                Resultados Consolidados por Pregunta
            </h5>
            <small class="text-muted">Suma de votos de todas las encuestas del grupo</small>
        </div>
        <div class="card-body p-4">
            @foreach($questionStats as $index => $qStat)
                <div class="mb-5 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <!-- Título de la Pregunta -->
                    <div class="mb-4">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-success me-3" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-grow-1">
                                <h5 class="mb-2">{{ $qStat['question']->question_text }}</h5>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-check-circle"></i>
                                    Total de votos válidos (todas las encuestas): <strong>{{ number_format($qStat['total_votes']) }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones y Resultados Consolidados -->
                    <div class="row g-3">
                        @foreach($qStat['options'] as $optStat)
                            <div class="col-md-6">
                                <div class="p-3 border rounded" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">{{ $optStat['option']->option_text }}</h6>
                                        <span class="badge bg-success">{{ number_format($optStat['votes']) }} votos</span>
                                    </div>

                                    <!-- Barra de Progreso -->
                                    <div class="progress mb-2" style="height: 25px;">
                                        <div class="progress-bar bg-success"
                                             role="progressbar"
                                             style="width: {{ $optStat['percentage'] }}%"
                                             aria-valuenow="{{ $optStat['percentage'] }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <strong>{{ $optStat['percentage'] }}%</strong>
                                        </div>
                                    </div>

                                    <small class="text-muted">
                                        <i class="bi bi-collection"></i>
                                        Suma de {{ $stats['total_surveys'] }} encuestas
                                    </small>
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
                    <small>El grupo no contiene encuestas o las encuestas no tienen preguntas</small>
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

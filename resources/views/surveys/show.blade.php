@extends('layouts.app')

@section('title', $survey->title)

@section('meta_description', $survey->description ?? 'Participa en esta encuesta y comparte tu opinión')

@section('og_image_full', $survey->og_image ? asset('storage/' . $survey->og_image) : ($survey->banner ? asset('storage/' . $survey->banner) : url('images/default-survey-preview.jpg')))

@section('og_title', $survey->title)
@section('og_description', $survey->description ?? 'Participa en esta encuesta y comparte tu opinión')

@section('content')
<div class="min-vh-100 d-flex align-items-center position-relative" style="background: linear-gradient(135deg, #fff9e6 0%, #e6f2ff 50%, #ffe6e6 100%);">
    <!-- Efecto difuminado de fondo - Colores de Colombia -->
    <div class="position-absolute w-100 h-100" style="overflow: hidden; z-index: 0;">
        <div class="blur-circle" style="position: absolute; top: -10%; left: -5%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255, 209, 0, 0.2) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="blur-circle" style="position: absolute; bottom: -15%; right: -5%; width: 550px; height: 550px; background: radial-gradient(circle, rgba(206, 17, 38, 0.15) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="blur-circle" style="position: absolute; top: 30%; right: 10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(0, 56, 168, 0.15) 0%, transparent 70%); filter: blur(50px);"></div>
        <div class="blur-circle" style="position: absolute; top: 50%; left: 20%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255, 209, 0, 0.12) 0%, transparent 70%); filter: blur(55px);"></div>
    </div>

    <div class="container py-5 position-relative" style="z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <!-- Card principal -->
                <div class="card border-0 rounded-4 overflow-hidden" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);">
                    <!-- Banner -->
                    @if($survey->banner)
                        <div class="banner-wrapper-form">
                            <img src="{{ asset('storage/' . $survey->banner) }}"
                                 alt="Banner de {{ $survey->title }}"
                                 class="w-100 banner-img-form"
                                 style="display: block; height: auto; max-height: 400px; object-fit: contain; background: #f8f9fa;">
                        </div>
                    @else
                        <div class="card-img-top bg-gradient d-flex align-items-center justify-content-center"
                             style="height: 200px; background: linear-gradient(180deg, #FCD116 0%, #FCD116 50%, #003893 75%, #CE1126 100%);">
                            <i class="bi bi-clipboard-data text-white" style="font-size: 4rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>
                        </div>
                    @endif

                    <div class="card-body p-4 p-md-5">
                        <!-- Título y descripción -->
                        <div class="text-center mb-5">
                            <h1 class="display-5 fw-bold text-dark mb-3">{{ $survey->title }}</h1>
                            @if($survey->description)
                                <p class="lead text-muted">{{ $survey->description }}</p>
                            @endif
                            <hr class="my-4">
                        </div>

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('no_tokens'))
                            <!-- No hay tokens disponibles -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="bi bi-exclamation-circle text-warning" style="font-size: 5rem;"></i>
                                </div>
                                <h2 class="h3 fw-bold text-dark mb-3">Encuesta No Disponible</h2>
                                <p class="lead text-muted mb-4">Esta encuesta ha alcanzado su límite de participantes.</p>
                                <p class="text-muted">
                                    <i class="bi bi-info-circle"></i> No hay cupos disponibles en este momento.
                                </p>
                            </div>
                        @elseif($hasVoted)
                            <!-- Ya votó -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                                </div>
                                <h2 class="h3 fw-bold text-dark mb-3">¡Gracias por participar!</h2>
                                <p class="lead text-muted mb-4">Ya has votado en esta encuesta anteriormente.</p>
                                <p class="text-muted">
                                    <i class="bi bi-info-circle"></i> Solo se permite un voto por persona.
                                </p>
                            </div>
                        @else
                            <!-- Formulario de votación -->
                            <form method="POST" action="{{ route('surveys.vote', $survey->public_slug) }}" id="voteForm">
                                @csrf
                                <input type="hidden" name="fingerprint" id="fingerprint">

                                @if(isset($token) && $token)
                                    <input type="hidden" name="token" value="{{ $token }}">
                                @endif

                                <!-- Datos del dispositivo para detección inteligente de fraude -->
                                <input type="hidden" name="device_data[user_agent]" id="device_user_agent">
                                <input type="hidden" name="device_data[platform]" id="device_platform">
                                <input type="hidden" name="device_data[screen_resolution]" id="device_resolution">
                                <input type="hidden" name="device_data[hardware_concurrency]" id="device_cpu">

                                <!-- Honeypot fields - campos trampa para bots (invisibles) -->
                                <input type="text" name="website" id="website" style="position:absolute;left:-9999px;width:1px;height:1px;" tabindex="-1" autocomplete="off">
                                <input type="text" name="url_field" id="url_field" style="position:absolute;left:-9999px;width:1px;height:1px;" tabindex="-1" autocomplete="off">

                                @foreach($survey->questions as $question)
                                    <div class="mb-5 pb-4 border-bottom">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                                 style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">{{ $loop->iteration }}</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="fw-semibold text-dark mb-3">{{ $question->question_text }}</h5>

                                                @if($question->question_type === 'single_choice')
                                                    <!-- Radio buttons para selección única -->
                                                    @foreach($question->options->shuffle() as $option)
                                                        <style>
                                                            #option{{ $option->id }}:checked {
                                                                background-color: {{ $option->color ?? '#0d6efd' }};
                                                                border-color: {{ $option->color ?? '#0d6efd' }};
                                                            }
                                                        </style>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="radio"
                                                                   name="answers[{{ $question->id }}]"
                                                                   value="{{ $option->id }}"
                                                                   id="option{{ $option->id }}"
                                                                   required>
                                                            <label class="form-check-label fw-medium" for="option{{ $option->id }}">
                                                                @if($option->image)
                                                                    <div class="mb-2">
                                                                        <img src="{{ asset('storage/' . $option->image) }}"
                                                                             alt="{{ $option->option_text }}"
                                                                             class="img-fluid rounded shadow-sm"
                                                                             style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                                                    </div>
                                                                @endif
                                                                {{ $option->option_text }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <!-- Checkboxes para selección múltiple -->
                                                    @foreach($question->options as $option)
                                                        <style>
                                                            #option{{ $option->id }}:checked {
                                                                background-color: {{ $option->color ?? '#0d6efd' }};
                                                                border-color: {{ $option->color ?? '#0d6efd' }};
                                                            }
                                                        </style>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="answers[{{ $question->id }}][]"
                                                                   value="{{ $option->id }}"
                                                                   id="option{{ $option->id }}">
                                                            <label class="form-check-label fw-medium" for="option{{ $option->id }}">
                                                                @if($option->image)
                                                                    <div class="mb-2">
                                                                        <img src="{{ asset('storage/' . $option->image) }}"
                                                                             alt="{{ $option->option_text }}"
                                                                             class="img-fluid rounded shadow-sm"
                                                                             style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                                                    </div>
                                                                @endif
                                                                {{ $option->option_text }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- Botón de envío -->
                                <div class="d-grid gap-2 mt-5">
                                    <button type="submit" class="btn btn-lg text-white fw-bold shadow"
                                            style="background: linear-gradient(90deg, #FCD116 0%, #003893 50%, #CE1126 100%); padding: 1rem; border: none; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                        <i class="bi bi-send-fill"></i> Enviar mi voto
                                    </button>
                                </div>

                                <div class="text-center mt-4">
                                    <small class="text-muted">
                                        <i class="bi bi-shield-check"></i> Tu voto es anónimo y seguro
                                    </small>
                                </div>
                            </form>
                        @endif
                    </div>

                    <!-- Footer del card -->
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

<script>
// ============================================
// SISTEMA AVANZADO DE FINGERPRINTING
// ============================================

// 1. Canvas Fingerprinting
function getCanvasFingerprint() {
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 200;
        canvas.height = 50;

        // Dibujar texto con diferentes fuentes y tamaños
        ctx.textBaseline = 'top';
        ctx.font = '14px "Arial"';
        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = '#069';
        ctx.fillText('Survey Fingerprint 🎨', 2, 15);
        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
        ctx.fillText('Canvas FP', 4, 17);

        return canvas.toDataURL();
    } catch (e) {
        return 'canvas-not-supported';
    }
}

// 2. WebGL Fingerprinting
function getWebGLFingerprint() {
    try {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

        if (!gl) return 'webgl-not-supported';

        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
        return JSON.stringify({
            vendor: gl.getParameter(gl.VENDOR),
            renderer: gl.getParameter(gl.RENDERER),
            version: gl.getParameter(gl.VERSION),
            shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
            unmaskedVendor: debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : 'unknown',
            unmaskedRenderer: debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'unknown'
        });
    } catch (e) {
        return 'webgl-error';
    }
}

// 3. Detectar fuentes instaladas
function getFontsFingerprint() {
    const baseFonts = ['monospace', 'sans-serif', 'serif'];
    const testFonts = [
        'Arial', 'Verdana', 'Times New Roman', 'Courier New', 'Georgia',
        'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS', 'Trebuchet MS',
        'Impact', 'Lucida Sans', 'Tahoma', 'Calibri', 'Cambria',
        'Segoe UI', 'Helvetica', 'Geneva', 'Monaco', 'Consolas'
    ];

    const testString = 'mmmmmmmmmmlli';
    const testSize = '72px';
    const h = document.getElementsByTagName('body')[0];

    const s = document.createElement('span');
    s.style.fontSize = testSize;
    s.innerHTML = testString;
    const defaultWidth = {};
    const defaultHeight = {};

    for (const baseFont of baseFonts) {
        s.style.fontFamily = baseFont;
        h.appendChild(s);
        defaultWidth[baseFont] = s.offsetWidth;
        defaultHeight[baseFont] = s.offsetHeight;
        h.removeChild(s);
    }

    const detected = [];
    for (const font of testFonts) {
        let detected_font = false;
        for (const baseFont of baseFonts) {
            s.style.fontFamily = font + ',' + baseFont;
            h.appendChild(s);
            const matched = (s.offsetWidth !== defaultWidth[baseFont] || s.offsetHeight !== defaultHeight[baseFont]);
            h.removeChild(s);
            if (matched) {
                detected_font = true;
                break;
            }
        }
        if (detected_font) {
            detected.push(font);
        }
    }

    return detected.join(',');
}

// 4. Detectar plugins del navegador
function getPluginsFingerprint() {
    try {
        const plugins = [];
        for (let i = 0; i < navigator.plugins.length; i++) {
            const plugin = navigator.plugins[i];
            plugins.push(plugin.name + '::' + plugin.description);
        }
        return plugins.join('|');
    } catch (e) {
        return 'plugins-not-available';
    }
}

// 5. Información avanzada de hardware
function getHardwareFingerprint() {
    const nav = window.navigator;
    return JSON.stringify({
        cpuCores: nav.hardwareConcurrency || 'unknown',
        deviceMemory: nav.deviceMemory || 'unknown', // GB de RAM
        platform: nav.platform,
        oscpu: nav.oscpu || 'unknown',
        vendor: nav.vendor || 'unknown',
        maxTouchPoints: nav.maxTouchPoints || 0
    });
}

// 6. Audio Fingerprinting
function getAudioFingerprint() {
    try {
        const audioContext = window.OfflineAudioContext || window.webkitOfflineAudioContext;
        if (!audioContext) return 'audio-not-supported';

        const context = new audioContext(1, 44100, 44100);
        const oscillator = context.createOscillator();
        oscillator.type = 'triangle';
        oscillator.frequency.value = 10000;

        const compressor = context.createDynamicsCompressor();
        oscillator.connect(compressor);
        compressor.connect(context.destination);
        oscillator.start(0);
        context.startRendering();

        return 'audio-context-created';
    } catch (e) {
        return 'audio-error';
    }
}

// Función para leer cookies
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Función para establecer cookies de larga duración
function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Strict";
}

// Generar fingerprint avanzado único con TRIPLE PROTECCIÓN
function generateFingerprint() {
    // 1. Intentar recuperar de COOKIE (persiste incluso en incógnito si no se cierra)
    let fingerprint = getCookie('device_fingerprint');

    // 2. Si no hay en cookie, intentar desde localStorage
    if (!fingerprint) {
        fingerprint = localStorage.getItem('survey_fingerprint');
    }

    // 3. Si aún no hay fingerprint, generarlo desde cero
    if (!fingerprint) {
        const nav = window.navigator;
        const screen = window.screen;

        // Recopilar TODOS los datos del dispositivo
        const basicData = [
            nav.userAgent,
            nav.language,
            nav.languages ? nav.languages.join(',') : '',
            nav.platform,
            nav.hardwareConcurrency || 'unknown',
            screen.colorDepth,
            screen.width + 'x' + screen.height,
            screen.availWidth + 'x' + screen.availHeight,
            screen.pixelDepth,
            new Date().getTimezoneOffset(),
            !!window.sessionStorage,
            !!window.localStorage,
            !!window.indexedDB,
            typeof(window.openDatabase) !== 'undefined',
            nav.cookieEnabled,
            nav.doNotTrack || 'unknown',
            nav.maxTouchPoints || 0,
            window.devicePixelRatio || 1
        ].join('|');

        // Datos avanzados
        const canvasHash = hashString(getCanvasFingerprint());
        const webglHash = hashString(getWebGLFingerprint());
        const fontsHash = hashString(getFontsFingerprint());
        const pluginsHash = hashString(getPluginsFingerprint());
        const hardwareHash = hashString(getHardwareFingerprint());
        const audioHash = hashString(getAudioFingerprint());

        // Combinar todos los datos
        const combinedData = basicData + '|' + canvasHash + '|' + webglHash + '|' +
                           fontsHash + '|' + pluginsHash + '|' + hardwareHash + '|' + audioHash;

        // Generar hash final
        const finalHash = hashString(combinedData);
        fingerprint = 'fp_' + finalHash + '_' + Date.now().toString(36);
    }

    // GUARDAR EN TRIPLE UBICACIÓN para máxima persistencia
    // 1. LocalStorage (se borra en incógnito al cerrar)
    try {
        localStorage.setItem('survey_fingerprint', fingerprint);
    } catch (e) {
        console.log('LocalStorage no disponible');
    }

    // 2. Cookie de 365 días (MUY PERSISTENTE - incluso sobrevive a limpiezas parciales)
    setCookie('device_fingerprint', fingerprint, 365);

    // 3. Cookie específica de esta encuesta
    setCookie('survey_{{ $survey->id }}_fp', fingerprint, 365);

    return fingerprint;
}

// Función auxiliar para generar hash de strings
function hashString(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    return Math.abs(hash).toString(36);
}

// Verificar si ya votó y redirigir
async function checkIfAlreadyVoted(fingerprint) {
    try {
        const response = await fetch('{{ route('surveys.check-vote', $survey->public_slug) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ fingerprint: fingerprint })
        });

        const data = await response.json();

        if (data.has_voted) {
            // Redirigir a la página de resultados
            window.location.href = '{{ route('surveys.thanks', $survey->public_slug) }}';
        }
    } catch (error) {
        console.error('Error verificando voto:', error);
    }
}

// Verificación del lado del cliente antes de enviar
function validateBeforeSubmit(event) {
    // Verificar si existe cookie específica de esta encuesta
    const surveyVoteCookie = getCookie('survey_{{ $survey->id }}_voted');

    if (surveyVoteCookie) {
        event.preventDefault();
        alert('Ya has votado en esta encuesta anteriormente. Solo se permite un voto por dispositivo.');
        window.location.href = '{{ route('surveys.thanks', $survey->public_slug) }}';
        return false;
    }

    return true;
}

// Establecer fingerprint y datos del dispositivo al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const fingerprint = generateFingerprint();
    const fingerprintInput = document.getElementById('fingerprint');

    if (fingerprintInput) {
        fingerprintInput.value = fingerprint;

        // Guardar datos del dispositivo para detección inteligente
        const nav = window.navigator;
        const screen = window.screen;

        document.getElementById('device_user_agent').value = nav.userAgent || '';
        document.getElementById('device_platform').value = nav.platform || '';
        document.getElementById('device_resolution').value = screen.width + 'x' + screen.height;
        document.getElementById('device_cpu').value = nav.hardwareConcurrency || 0;

        // Verificar si ya votó y redirigir automáticamente
        checkIfAlreadyVoted(fingerprint);

        // Agregar validación al formulario
        const voteForm = document.getElementById('voteForm');
        if (voteForm) {
            voteForm.addEventListener('submit', validateBeforeSubmit);
        }
    }

    // Animación suave al hacer scroll a preguntas
    const formChecks = document.querySelectorAll('.form-check-input');
    formChecks.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-check').style.transform = 'scale(1.02)';
            this.closest('.form-check').style.transition = 'transform 0.2s';
        });

        input.addEventListener('blur', function() {
            this.closest('.form-check').style.transform = 'scale(1)';
        });
    });
});
</script>

<style>
/* Estilos del banner del formulario */
.banner-wrapper-form {
    overflow: hidden;
    background: #f8f9fa;
    min-height: 200px;
    max-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.banner-img-form {
    object-fit: contain !important;
    width: 100%;
    height: auto;
}

.form-check {
    transition: all 0.3s ease;
    padding: 0.75rem;
    border-radius: 0.5rem;
}

.form-check:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.15rem;
    cursor: pointer;
}

.form-check-label {
    cursor: pointer;
    font-size: 1.05rem;
}

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

/* Responsive adjustments */
@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
    }

    .lead {
        font-size: 1rem;
    }

    .card-body {
        padding: 1.5rem !important;
    }

    .form-check-label {
        font-size: 0.95rem;
    }

    /* Banner móvil */
    .banner-wrapper-form {
        min-height: 150px !important;
        max-height: 250px !important;
    }

    .banner-img-form {
        max-height: 250px !important;
    }
}

@media (max-width: 576px) {
    .card-img-top {
        height: 150px !important;
    }

    .bg-primary.rounded-circle {
        width: 35px !important;
        height: 35px !important;
        font-size: 0.9rem;
    }

    /* Banner extra pequeño */
    .banner-wrapper-form {
        min-height: 120px !important;
        max-height: 200px !important;
    }

    .banner-img-form {
        max-height: 200px !important;
    }
}
</style>
@endsection

 
<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TokenRedirectController extends Controller
{
    public function redirect(Request $request, string $publicSlug)
    {
        // ========================================================================
        // DETECCIÓN DE BOTS DE REDES SOCIALES (Facebook, Twitter, etc.)
        // Estos bots hacen scraping para generar previews y NO deben consumir tokens
        // ========================================================================
        $userAgent = $request->userAgent();

        // Lista expandida de bots de redes sociales
        $socialMediaBots = [
            // Facebook bots (IMPORTANTE: para anuncios de Facebook)
            'facebookexternalhit',      // Bot principal de Facebook para previews
            'FacebookExternalHit',      // Variante con mayúsculas
            'facebookcatalog',          // Bot de catálogo de Facebook (para anuncios)
            'Facebot',                  // Otro bot de Facebook
            'meta-externalagent',       // Meta (Facebook) external agent
            'facebookplatform',         // Plataforma de Facebook
            'facebook',                 // Fallback para cualquier bot de Facebook

            // WhatsApp (propiedad de Meta/Facebook)
            'WhatsApp',

            // Twitter/X
            'Twitterbot',
            'TwitterBot',

            // LinkedIn
            'LinkedInBot',
            'linkedin',

            // Telegram
            'TelegramBot',
            'Telegram',

            // Otros bots de redes sociales
            'Slackbot',
            'Slack-ImgProxy',
            'Discordbot',
            'Discord',
            'Pinterestbot',
            'Instagram',
            'SkypeUriPreview',
            'vkShare',
            'VK ',
            'reddit',
            'Snapchat',
        ];

        $isSocialMediaBot = false;
        $detectedBot = null;

        foreach ($socialMediaBots as $botName) {
            if (stripos($userAgent, $botName) !== false) {
                $isSocialMediaBot = true;
                $detectedBot = $botName;
                break;
            }
        }

        // Si es un bot de redes sociales, redirigir SIN asignar token
        if ($isSocialMediaBot) {
            // Log para debugging (útil para ver qué bots están accediendo)
            \Log::info('Bot de red social detectado', [
                'bot' => $detectedBot,
                'user_agent' => $userAgent,
                'slug' => $publicSlug,
                'ip' => $request->ip()
            ]);

            // Buscar la encuesta para obtener el slug real
            $survey = Survey::where('public_slug', $publicSlug)->with('group')->firstOrFail();

            // Redirigir directamente a la encuesta sin token (solo para preview)
            return redirect()->route('surveys.show', ['publicSlug' => $publicSlug]);
        }

        // Para usuarios reales, mostrar página de carga intermedia
        // Esta página esperará 1 segundo antes de asignar el token
        return view('surveys.token-loading', [
            'publicSlug' => $publicSlug
        ]);

        // NOTA: El código siguiente ya NO se ejecutará directamente
        // Se ejecutará a través del método assignToken() después del delay

        // Buscar encuesta por public_slug (ofuscado)
        $survey = Survey::where('public_slug', $publicSlug)->with('group')->firstOrFail();

        // Si la encuesta pertenece a un grupo, redirigir a la ruta con grupo
        if ($survey->survey_group_id && $survey->group && $survey->group->slug) {
            $queryParams = $request->query(); // Mantener todos los parámetros (token, source, etc.)
            return redirect()->route('token.redirect.group', [
                'groupSlug' => $survey->group->slug,
                'publicSlug' => $publicSlug
            ] + $queryParams);
        }

        // ========================================================================
        // VERIFICAR SI YA VIENE UN TOKEN EN LA URL
        // ========================================================================
        $tokenString = $request->query('token');

        if ($tokenString) {
            // Si viene un token en la URL, redirigir directamente a /survey/ con ese token
            // NO generar un token nuevo
            return redirect()->route('surveys.show', [
                'publicSlug' => $publicSlug,
                'token' => $tokenString
            ]);
        }

        // ========================================================================
        // DETECCIÓN AVANZADA: Buscar si este dispositivo ya votó en ESTA encuesta
        // Comparar por características de hardware (User-Agent, etc.)
        // ========================================================================
        $fingerprint = $request->cookie('survey_fingerprint');

        // Primero verificar por fingerprint si existe
        if ($fingerprint) {
            $previousVote = \App\Models\Vote::where('survey_id', $survey->id)
                ->where('fingerprint', $fingerprint)
                ->where('is_valid', true)
                ->first();

            if ($previousVote && $previousVote->survey_token_id) {
                // Ya votó - Reusar el MISMO token
                $usedToken = \App\Models\SurveyToken::find($previousVote->survey_token_id);

                if ($usedToken) {
                    return redirect()->route('surveys.show', [
                        'publicSlug' => $publicSlug,
                        'token' => $usedToken->token
                    ]);
                }
            }
        }

        // Si no hay fingerprint, buscar por User-Agent (detección de dispositivo)
        $userAgent = $request->userAgent();
        if ($userAgent) {
            $previousVote = \App\Models\Vote::where('survey_id', $survey->id)
                ->where('is_valid', true)
                ->where('user_agent', 'LIKE', '%' . substr($userAgent, 0, 50) . '%')
                ->first();

            if ($previousVote && $previousVote->survey_token_id) {
                // Ya votó - Reusar el MISMO token
                $usedToken = \App\Models\SurveyToken::find($previousVote->survey_token_id);

                if ($usedToken) {
                    return redirect()->route('surveys.show', [
                        'publicSlug' => $publicSlug,
                        'token' => $usedToken->token
                    ]);
                }
            }
        }

        // ========================================================================
        // SISTEMA DE POOL DE TOKENS: Usar tokens pre-generados del pool
        // Solo si NO viene un token en la URL Y NO ha votado antes
        // ========================================================================

        // Intentar asignar un token disponible del pool (con bloqueo para evitar condiciones de carrera)
        DB::beginTransaction();

        try {
            // Buscar un token pendiente disponible (lockForUpdate para evitar asignación duplicada)
            $token = SurveyToken::where('survey_id', $survey->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            // Si NO hay tokens disponibles, mostrar página de encuesta no disponible
            if (!$token) {
                DB::commit();

                // Redirigir a vista dedicada de encuesta no disponible
                return view('surveys.unavailable');
            }

            // OPCIONAL: Actualizar información del token (source, campaign_id) si se proporcionan
            // Esto permite rastrear de dónde viene cada visitante
            $source = $request->query('source');
            $campaignId = $request->query('campaign_id');

            if ($source && $token->source === 'manual') {
                $token->source = $source;
            }
            if ($campaignId && !$token->campaign_id) {
                $token->campaign_id = $campaignId;
            }

            $token->user_agent = $request->userAgent();
            $token->save();

            DB::commit();

            // Redirigir a la encuesta con el token asignado del pool
            return redirect()->route('surveys.show', [
                'publicSlug' => $publicSlug,
                'token' => $token->token
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // En caso de error, redirigir sin token
            return redirect()->route('surveys.show', [
                'publicSlug' => $publicSlug
            ])->with('error', 'Ocurrió un error al procesar tu solicitud.');
        }
    }

    /**
     * Redirigir con slug del grupo
     */
    public function redirectWithGroup(Request $request, string $groupSlug, string $publicSlug)
    {
        // ========================================================================
        // DETECCIÓN DE BOTS DE REDES SOCIALES (Facebook, Twitter, etc.)
        // ========================================================================
        $userAgent = $request->userAgent();

        // Lista expandida de bots de redes sociales
        $socialMediaBots = [
            // Facebook bots (IMPORTANTE: para anuncios de Facebook)
            'facebookexternalhit',
            'FacebookExternalHit',
            'facebookcatalog',
            'Facebot',
            'meta-externalagent',
            'facebookplatform',
            'facebook',

            // WhatsApp
            'WhatsApp',

            // Twitter/X
            'Twitterbot',
            'TwitterBot',

            // LinkedIn
            'LinkedInBot',
            'linkedin',

            // Telegram
            'TelegramBot',
            'Telegram',

            // Otros
            'Slackbot',
            'Slack-ImgProxy',
            'Discordbot',
            'Discord',
            'Pinterestbot',
            'Instagram',
            'SkypeUriPreview',
            'vkShare',
            'VK ',
            'reddit',
            'Snapchat',
        ];

        $isSocialMediaBot = false;
        $detectedBot = null;

        foreach ($socialMediaBots as $botName) {
            if (stripos($userAgent, $botName) !== false) {
                $isSocialMediaBot = true;
                $detectedBot = $botName;
                break;
            }
        }

        // Si es un bot de redes sociales, redirigir SIN asignar token
        if ($isSocialMediaBot) {
            // Log para debugging
            \Log::info('Bot de red social detectado (grupo)', [
                'bot' => $detectedBot,
                'user_agent' => $userAgent,
                'group' => $groupSlug,
                'slug' => $publicSlug,
                'ip' => $request->ip()
            ]);

            return redirect()->route('surveys.show.group', [
                'groupSlug' => $groupSlug,
                'publicSlug' => $publicSlug
            ]);
        }

        // Para usuarios reales, mostrar página de carga intermedia
        return view('surveys.token-loading', [
            'publicSlug' => $publicSlug,
            'groupSlug' => $groupSlug
        ]);

        // NOTA: El código siguiente ya NO se ejecutará directamente
        // Se ejecutará a través del método assignToken() después del delay

        // Buscar el grupo por slug
        $group = \App\Models\SurveyGroup::where('slug', $groupSlug)->firstOrFail();

        // Buscar encuesta por public_slug (ofuscado) y verificar que pertenece al grupo
        $survey = Survey::where('public_slug', $publicSlug)
            ->where('survey_group_id', $group->id)
            ->firstOrFail();

        // ========================================================================
        // VERIFICAR SI YA VIENE UN TOKEN EN LA URL
        // ========================================================================
        $tokenString = $request->query('token');

        if ($tokenString) {
            // Si viene un token en la URL, redirigir directamente con ese token
            return redirect()->route('surveys.show.group', [
                'groupSlug' => $groupSlug,
                'publicSlug' => $publicSlug,
                'token' => $tokenString
            ]);
        }

        // ========================================================================
        // VALIDACIÓN DE GRUPO: Si ya votó en el grupo, reusar el mismo token
        // ========================================================================
        $fingerprint = $request->cookie('survey_fingerprint');

        if ($group->restrict_voting && $fingerprint) {
            // Verificar si ya votó en alguna encuesta del grupo
            $usedToken = $group->getUsedTokenByFingerprint($fingerprint);

            if ($usedToken) {
                // Redirigir con el MISMO token que ya usó (no dar uno nuevo)
                return redirect()->route('surveys.show.group', [
                    'groupSlug' => $groupSlug,
                    'publicSlug' => $publicSlug,
                    'token' => $usedToken->token
                ]);
            }
        }

        // ========================================================================
        // DETECCIÓN AVANZADA: Buscar si este dispositivo ya votó (sin fingerprint)
        // Comparar por características de hardware (User-Agent, etc.)
        // ========================================================================
        $deviceMatcher = new \App\Services\DeviceFingerprintMatcher();

        // Buscar votos previos de este dispositivo en CUALQUIER encuesta del grupo
        $previousVote = $this->findPreviousVoteInGroup($group->id, $request, $deviceMatcher);

        if ($previousVote && $previousVote->survey_token_id) {
            // Ya votó antes - Reusar el MISMO token
            $usedToken = \App\Models\SurveyToken::find($previousVote->survey_token_id);

            if ($usedToken) {
                return redirect()->route('surveys.show.group', [
                    'groupSlug' => $groupSlug,
                    'publicSlug' => $publicSlug,
                    'token' => $usedToken->token
                ]);
            }
        }

        // ========================================================================
        // SISTEMA DE POOL DE TOKENS: Usar tokens pre-generados del pool
        // ========================================================================

        DB::beginTransaction();

        try {
            // Buscar un token pendiente disponible
            $token = SurveyToken::where('survey_id', $survey->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            // Si NO hay tokens disponibles, mostrar página de encuesta no disponible
            if (!$token) {
                DB::commit();
                return view('surveys.unavailable');
            }

            // Actualizar información del token
            $source = $request->query('source');
            $campaignId = $request->query('campaign_id');

            if ($source && $token->source === 'manual') {
                $token->source = $source;
            }
            if ($campaignId && !$token->campaign_id) {
                $token->campaign_id = $campaignId;
            }

            $token->user_agent = $request->userAgent();
            $token->save();

            DB::commit();

            // Redirigir a la encuesta con el token asignado del pool
            return redirect()->route('surveys.show.group', [
                'groupSlug' => $groupSlug,
                'publicSlug' => $publicSlug,
                'token' => $token->token
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // En caso de error, redirigir sin token
            return redirect()->route('surveys.show.group', [
                'groupSlug' => $groupSlug,
                'publicSlug' => $publicSlug
            ])->with('error', 'Ocurrió un error al procesar tu solicitud.');
        }
    }

    /**
     * Buscar voto previo de este dispositivo en el grupo
     */
    private function findPreviousVoteInGroup(int $groupId, Request $request, $deviceMatcher)
    {
        $userAgent = $request->userAgent();

        if (!$userAgent) {
            return null;
        }

        // Buscar votos en encuestas de este grupo con User-Agent similar
        return \App\Models\Vote::join('questions', 'votes.question_id', '=', 'questions.id')
            ->join('surveys', 'questions.survey_id', '=', 'surveys.id')
            ->where('surveys.survey_group_id', $groupId)
            ->where('votes.is_valid', true)
            ->where('votes.user_agent', 'LIKE', '%' . substr($userAgent, 0, 50) . '%')
            ->select('votes.*')
            ->first();
    }

    /**
     * API: Asignar token después del delay de 1 segundo
     * Este método se llama desde JavaScript después de mostrar la página de carga
     */
    public function assignToken(Request $request, string $publicSlug)
    {
        $groupSlug = $request->input('groupSlug');

        // Si tiene grupo, usar la lógica de grupo
        if ($groupSlug) {
            return $this->assignTokenWithGroup($request, $groupSlug, $publicSlug);
        }

        // Buscar encuesta por public_slug
        $survey = Survey::where('public_slug', $publicSlug)->with('group')->firstOrFail();

        // Si la encuesta pertenece a un grupo, redirigir a la ruta con grupo
        if ($survey->survey_group_id && $survey->group && $survey->group->slug) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('token.redirect.group', [
                    'groupSlug' => $survey->group->slug,
                    'publicSlug' => $publicSlug
                ])
            ]);
        }

        // Verificar si ya viene un token en la URL
        $tokenString = $request->query('token');
        if ($tokenString) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('surveys.show', [
                    'publicSlug' => $publicSlug,
                    'token' => $tokenString
                ])
            ]);
        }

        // Verificar si ya votó por fingerprint
        $fingerprint = $request->cookie('survey_fingerprint');
        if ($fingerprint) {
            $previousVote = \App\Models\Vote::where('survey_id', $survey->id)
                ->where('fingerprint', $fingerprint)
                ->where('is_valid', true)
                ->first();

            if ($previousVote && $previousVote->survey_token_id) {
                $usedToken = \App\Models\SurveyToken::find($previousVote->survey_token_id);
                if ($usedToken) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => route('surveys.show', [
                            'publicSlug' => $publicSlug,
                            'token' => $usedToken->token
                        ])
                    ]);
                }
            }
        }

        // Liberar reservas expiradas primero
        SurveyToken::releaseExpiredReservations();

        // Obtener ID de sesión única para este usuario
        $sessionId = $request->session()->getId();

        // Verificar si esta sesión ya tiene un token reservado
        $existingReservedToken = SurveyToken::findReservedBySession($survey->id, $sessionId);
        if ($existingReservedToken) {
            // Ya tiene un token reservado, devolver ese mismo
            return response()->json([
                'success' => true,
                'redirect_url' => route('surveys.show', [
                    'publicSlug' => $publicSlug,
                    'token' => $existingReservedToken->token
                ])
            ]);
        }

        // Asignar un nuevo token del pool y RESERVARLO por 5 minutos
        DB::beginTransaction();
        try {
            // Buscar un token disponible (pending, NO reserved)
            $token = SurveyToken::where('survey_id', $survey->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$token) {
                DB::commit();
                return response()->json([
                    'success' => false,
                    'redirect_url' => route('surveys.unavailable')
                ]);
            }

            // RESERVAR el token para esta sesión por 5 minutos
            $token->reserve($sessionId);
            $token->user_agent = $request->userAgent();
            $token->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'redirect_url' => route('surveys.show', [
                    'publicSlug' => $publicSlug,
                    'token' => $token->token
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'redirect_url' => route('surveys.show', ['publicSlug' => $publicSlug])
            ]);
        }
    }

    /**
     * Asignar token para encuestas con grupo
     */
    private function assignTokenWithGroup(Request $request, string $groupSlug, string $publicSlug)
    {
        $group = \App\Models\SurveyGroup::where('slug', $groupSlug)->firstOrFail();
        $survey = Survey::where('public_slug', $publicSlug)
            ->where('survey_group_id', $group->id)
            ->firstOrFail();

        // Verificar si ya viene un token
        $tokenString = $request->query('token');
        if ($tokenString) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('surveys.show.group', [
                    'groupSlug' => $groupSlug,
                    'publicSlug' => $publicSlug,
                    'token' => $tokenString
                ])
            ]);
        }

        // Verificar si ya votó en el grupo
        $fingerprint = $request->cookie('survey_fingerprint');
        if ($group->restrict_voting && $fingerprint) {
            $usedToken = $group->getUsedTokenByFingerprint($fingerprint);
            if ($usedToken) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => route('surveys.show.group', [
                        'groupSlug' => $groupSlug,
                        'publicSlug' => $publicSlug,
                        'token' => $usedToken->token
                    ])
                ]);
            }
        }

        // Liberar reservas expiradas primero
        SurveyToken::releaseExpiredReservations();

        // Obtener ID de sesión única para este usuario
        $sessionId = $request->session()->getId();

        // Verificar si esta sesión ya tiene un token reservado
        $existingReservedToken = SurveyToken::findReservedBySession($survey->id, $sessionId);
        if ($existingReservedToken) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('surveys.show.group', [
                    'groupSlug' => $groupSlug,
                    'publicSlug' => $publicSlug,
                    'token' => $existingReservedToken->token
                ])
            ]);
        }

        // Asignar token del pool y RESERVARLO
        DB::beginTransaction();
        try {
            $token = SurveyToken::where('survey_id', $survey->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$token) {
                DB::commit();
                return response()->json([
                    'success' => false,
                    'redirect_url' => route('surveys.unavailable')
                ]);
            }

            // RESERVAR el token para esta sesión por 5 minutos
            $token->reserve($sessionId);
            $token->user_agent = $request->userAgent();
            $token->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'redirect_url' => route('surveys.show.group', [
                    'groupSlug' => $groupSlug,
                    'publicSlug' => $publicSlug,
                    'token' => $token->token
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'redirect_url' => route('surveys.show.group', [
                    'groupSlug' => $groupSlug,
                    'publicSlug' => $publicSlug
                ])
            ]);
        }
    }
}

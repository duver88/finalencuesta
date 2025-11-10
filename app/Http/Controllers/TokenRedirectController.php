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
        // SISTEMA DE POOL DE TOKENS: Usar tokens pre-generados del pool
        // Solo si NO viene un token en la URL
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
}

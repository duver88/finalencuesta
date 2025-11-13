<?php

namespace App\Http\Middleware;

use App\Models\Vote;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateVote
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $surveyId = $request->route('slug');

        if (!$surveyId) {
            return $next($request);
        }

        $fingerprint = $request->cookie('survey_fingerprint') ?? $request->input('fingerprint');
        $userAgent = $request->header('User-Agent');

        // Permitir bots de redes sociales (para previews de Facebook, Twitter, etc.)
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
        foreach ($socialMediaBots as $botName) {
            if (stripos($userAgent, $botName) !== false) {
                $isSocialMediaBot = true;
                break;
            }
        }

        // Si es un bot de redes sociales, permitir el acceso (para preview)
        if ($isSocialMediaBot) {
            return $next($request);
        }

        // Detectar bots maliciosos por User-Agent
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python-requests',
            'postman', 'insomnia', 'http', 'scrape', 'harvest'
        ];

        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                abort(403, 'Acceso denegado.');
            }
        }

        // Verificar User-Agent vacío (sospechoso)
        if (empty($userAgent)) {
            abort(403, 'Acceso denegado.');
        }

        // Verificar honeypot (campo oculto que los bots llenan)
        if ($request->filled('website') || $request->filled('url_field')) {
            abort(403, 'Acceso denegado.');
        }

        // Verificar si ya votó por fingerprint (si existe)
        $hasVotedByFingerprint = false;
        if ($fingerprint) {
            $hasVotedByFingerprint = Vote::where('survey_id', function($query) use ($surveyId) {
                $query->select('id')
                      ->from('surveys')
                      ->where('slug', $surveyId)
                      ->limit(1);
            })
            ->where('fingerprint', $fingerprint)
            ->exists();
        }

        if ($hasVotedByFingerprint) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Ya has votado en esta encuesta.',
                    'already_voted' => true
                ], 403);
            }

            return redirect()->back()->with('error', 'Ya has votado en esta encuesta.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyGroup;
use App\Models\Vote;
use App\Models\SurveyToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Reporte individual de una encuesta
     */
    public function surveyReport($id)
    {
        $survey = Survey::with(['questions.options', 'tokens'])->findOrFail($id);

        // Calcular estadísticas generales
        $stats = $this->calculateSurveyStats($survey);

        // Calcular estadísticas por pregunta/respuesta
        $questionStats = $this->calculateQuestionStats($survey);

        return view('admin.reports.survey', compact('survey', 'stats', 'questionStats'));
    }

    /**
     * Reporte consolidado de un grupo de encuestas
     */
    public function groupReport($id)
    {
        $group = SurveyGroup::with(['surveys.questions.options', 'surveys.tokens'])->findOrFail($id);

        // Calcular estadísticas consolidadas del grupo
        $stats = $this->calculateGroupStats($group);

        // Calcular estadísticas consolidadas por pregunta/respuesta
        $questionStats = $this->calculateGroupQuestionStats($group);

        return view('admin.reports.group', compact('group', 'stats', 'questionStats'));
    }

    /**
     * Calcular estadísticas de una encuesta individual
     */
    private function calculateSurveyStats($survey)
    {
        // Total de tokens generados (vistas)
        $totalTokens = SurveyToken::where('survey_id', $survey->id)->count();

        // Votos totales (todos los intentos)
        $totalVotes = Vote::where('survey_id', $survey->id)->count();

        // Votos válidos (con token y is_valid = true)
        $validVotes = Vote::where('survey_id', $survey->id)
            ->where('is_valid', true)
            ->whereNotNull('survey_token_id')
            ->distinct('survey_token_id')
            ->count('survey_token_id');

        // Votos no contados (duplicados o inválidos)
        $invalidVotes = $totalVotes - $validVotes;

        // Tokens usados
        $usedTokens = SurveyToken::where('survey_id', $survey->id)
            ->where('status', 'used')
            ->count();

        // Tasa de conversión (tokens usados / tokens generados)
        $conversionRate = $totalTokens > 0 ? ($usedTokens / $totalTokens) * 100 : 0;

        return [
            'total_tokens' => $totalTokens, // Vistas totales
            'total_votes' => $totalVotes,
            'valid_votes' => $validVotes,
            'invalid_votes' => $invalidVotes,
            'used_tokens' => $usedTokens,
            'conversion_rate' => round($conversionRate, 2),
        ];
    }

    /**
     * Calcular estadísticas por pregunta/respuesta de una encuesta
     */
    private function calculateQuestionStats($survey)
    {
        $questionStats = [];

        foreach ($survey->questions as $question) {
            $questionData = [
                'question' => $question,
                'options' => []
            ];

            // Total de votos válidos para esta pregunta
            $totalValidVotes = Vote::where('survey_id', $survey->id)
                ->where('question_id', $question->id)
                ->where('is_valid', true)
                ->whereNotNull('survey_token_id')
                ->distinct('survey_token_id')
                ->count('survey_token_id');

            foreach ($question->options as $option) {
                // Votos válidos para esta opción
                $optionVotes = Vote::where('survey_id', $survey->id)
                    ->where('question_id', $question->id)
                    ->where('question_option_id', $option->id)
                    ->where('is_valid', true)
                    ->whereNotNull('survey_token_id')
                    ->distinct('survey_token_id')
                    ->count('survey_token_id');

                // Porcentaje
                $percentage = $totalValidVotes > 0 ? ($optionVotes / $totalValidVotes) * 100 : 0;

                $questionData['options'][] = [
                    'option' => $option,
                    'votes' => $optionVotes,
                    'percentage' => round($percentage, 2)
                ];
            }

            $questionData['total_votes'] = $totalValidVotes;
            $questionStats[] = $questionData;
        }

        return $questionStats;
    }

    /**
     * Calcular estadísticas consolidadas de un grupo
     */
    private function calculateGroupStats($group)
    {
        $surveyIds = $group->surveys->pluck('id')->toArray();

        // Total de tokens generados (vistas) en todas las encuestas del grupo
        $totalTokens = SurveyToken::whereIn('survey_id', $surveyIds)->count();

        // Votos totales en todas las encuestas del grupo
        $totalVotes = Vote::whereIn('survey_id', $surveyIds)->count();

        // Votos válidos en todas las encuestas del grupo
        $validVotes = Vote::whereIn('survey_id', $surveyIds)
            ->where('is_valid', true)
            ->whereNotNull('survey_token_id')
            ->distinct('survey_token_id')
            ->count('survey_token_id');

        // Votos no contados
        $invalidVotes = $totalVotes - $validVotes;

        // Tokens usados
        $usedTokens = SurveyToken::whereIn('survey_id', $surveyIds)
            ->where('status', 'used')
            ->count();

        // Tasa de conversión
        $conversionRate = $totalTokens > 0 ? ($usedTokens / $totalTokens) * 100 : 0;

        return [
            'total_tokens' => $totalTokens,
            'total_votes' => $totalVotes,
            'valid_votes' => $validVotes,
            'invalid_votes' => $invalidVotes,
            'used_tokens' => $usedTokens,
            'conversion_rate' => round($conversionRate, 2),
            'total_surveys' => count($surveyIds),
        ];
    }

    /**
     * Calcular estadísticas consolidadas por pregunta/respuesta de un grupo
     * (asumiendo que todas las encuestas del grupo tienen las mismas preguntas)
     */
    private function calculateGroupQuestionStats($group)
    {
        $surveyIds = $group->surveys->pluck('id')->toArray();

        // Obtener la primera encuesta como referencia (asumimos que todas tienen las mismas preguntas)
        $referenceSurvey = $group->surveys->first();

        if (!$referenceSurvey) {
            return [];
        }

        $questionStats = [];

        foreach ($referenceSurvey->questions as $question) {
            $questionData = [
                'question' => $question,
                'options' => []
            ];

            // Total de votos válidos para esta pregunta EN TODAS LAS ENCUESTAS DEL GRUPO
            $totalValidVotes = Vote::whereIn('survey_id', $surveyIds)
                ->where('question_id', $question->id)
                ->where('is_valid', true)
                ->whereNotNull('survey_token_id')
                ->distinct('survey_token_id')
                ->count('survey_token_id');

            foreach ($question->options as $option) {
                // Votos válidos para esta opción EN TODAS LAS ENCUESTAS DEL GRUPO
                $optionVotes = Vote::whereIn('survey_id', $surveyIds)
                    ->where('question_id', $question->id)
                    ->where('question_option_id', $option->id)
                    ->where('is_valid', true)
                    ->whereNotNull('survey_token_id')
                    ->distinct('survey_token_id')
                    ->count('survey_token_id');

                // Porcentaje
                $percentage = $totalValidVotes > 0 ? ($optionVotes / $totalValidVotes) * 100 : 0;

                $questionData['options'][] = [
                    'option' => $option,
                    'votes' => $optionVotes,
                    'percentage' => round($percentage, 2)
                ];
            }

            $questionData['total_votes'] = $totalValidVotes;
            $questionStats[] = $questionData;
        }

        return $questionStats;
    }
}

<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyEntry;
use Illuminate\Support\Facades\Log;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Throwable;

/**
 * Menentukan validitas entri survei secara otomatis berdasarkan aturan validasi
 * yang didefinisikan pegawai pada survei.
 */
class EntryAnomalyValidator
{
    public const AUTO_INVALID_NOTE = 'Sistem mendeteksi adanya anomali pada entrian ini. Mohon periksa kembali data yang di input.';

    /**
     * Nama variabel disederhanakan menjadi identifier yang bisa dipakai di dalam formula,
     * contoh "Usia Responden" -> "usia_responden".
     */
    public static function variableIdentifier(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        $slug = trim($slug, '_');

        if ($slug === '') {
            return 'variabel';
        }

        return ctype_digit($slug[0]) ? 'v_'.$slug : $slug;
    }

    /**
     * Evaluasi semua aturan validasi survei terhadap nilai entri, lalu simpan hasilnya
     * (is_valid + note) langsung ke entri. Entri tanpa aturan otomatis dianggap valid.
     */
    public static function apply(SurveyEntry $entry): void
    {
        $entry->loadMissing(['survey.variables', 'values']);

        $anomalous = self::isAnomalous($entry->survey, $entry->values->pluck('value', 'survey_variable_id')->all());

        $entry->update([
            'is_valid' => $anomalous !== true,
            'note' => $anomalous === true ? self::AUTO_INVALID_NOTE : null,
        ]);
    }

    /**
     * @param  array<int, string|null>  $valuesByVariableId
     * @return bool|null  true = anomali terdeteksi, false = tidak ada anomali, null = aturan gagal dievaluasi (dianggap tidak ada anomali)
     */
    private static function isAnomalous(Survey $survey, array $valuesByVariableId): ?bool
    {
        $rules = self::rulesForSurvey($survey);

        if ($rules === []) {
            return false;
        }

        $context = self::buildContext($survey, $valuesByVariableId);
        $expressionLanguage = new ExpressionLanguage;

        foreach ($rules as $rule) {
            try {
                $isNormalCondition = (bool) $expressionLanguage->evaluate(self::normalizeExpression($rule), $context);
            } catch (Throwable $e) {
                Log::warning('Aturan validasi survei gagal dievaluasi.', [
                    'survey_id' => $survey->id,
                    'rule' => $rule,
                    'message' => $e->getMessage(),
                ]);

                return null;
            }

            if (! $isNormalCondition) {
                return true;
            }
        }

        return false;
    }

    /**
     * ID variabel yang diduga jadi penyebab anomali pada sebuah entri, dipakai untuk
     * menandai baris yang bermasalah di tabel "Entrian Variabel Validasi".
     *
     * @return list<int>
     */
    public static function anomalousVariableIds(SurveyEntry $entry): array
    {
        $entry->loadMissing(['survey.variables', 'values']);
        $survey = $entry->survey;
        $rules = self::rulesForSurvey($survey);

        if ($rules === []) {
            return [];
        }

        $valuesByVariableId = $entry->values->pluck('value', 'survey_variable_id')->all();
        $context = self::buildContext($survey, $valuesByVariableId);

        $identifierToVariableId = [];
        foreach ($survey->variables as $variable) {
            $identifierToVariableId[self::variableIdentifier($variable->name)] = $variable->id;
        }

        $expressionLanguage = new ExpressionLanguage;
        $anomalousVariableIds = [];

        foreach ($rules as $rule) {
            foreach (self::splitTopLevelAnd($rule) as $clause) {
                try {
                    $clausePasses = (bool) $expressionLanguage->evaluate(self::normalizeExpression($clause), $context);
                } catch (Throwable) {
                    continue;
                }

                if ($clausePasses) {
                    continue;
                }

                foreach ($identifierToVariableId as $identifier => $variableId) {
                    if (preg_match('/\b'.preg_quote($identifier, '/').'\b/', $clause) === 1) {
                        $anomalousVariableIds[$variableId] = true;
                    }
                }
            }
        }

        return array_keys($anomalousVariableIds);
    }

    /**
     * @param  array<int, string|null>  $valuesByVariableId
     * @return array<string, int|float|string|null>
     */
    private static function buildContext(Survey $survey, array $valuesByVariableId): array
    {
        $context = [];

        foreach ($survey->variables as $variable) {
            $raw = $valuesByVariableId[$variable->id] ?? null;
            $context[self::variableIdentifier($variable->name)] = is_numeric($raw) ? $raw + 0 : $raw;
        }

        return $context;
    }

    /**
     * @return list<string>
     */
    private static function rulesForSurvey(Survey $survey): array
    {
        $rules = collect($survey->validation_rules ?? [])
            ->filter(fn (mixed $rule): bool => is_string($rule) && filled($rule))
            ->map(fn (string $rule): string => trim($rule))
            ->values()
            ->all();

        if ($rules !== []) {
            return $rules;
        }

        $legacyFormula = trim((string) $survey->validation_formula);

        return $legacyFormula === '' ? [] : [$legacyFormula];
    }

    private static function normalizeExpression(string $expression): string
    {
        return preg_replace('/(?<![<>=!])=(?!=)/', '==', $expression) ?? $expression;
    }

    /**
     * Pecah formula jadi klausa-klausa yang digabung dengan "and" di tingkat atas
     * (tidak memecah "and" yang berada di dalam tanda kurung).
     *
     * @return list<string>
     */
    private static function splitTopLevelAnd(string $formula): array
    {
        $tokens = preg_split('/(\(|\)|\band\b)/i', $formula, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($tokens === false) {
            return [$formula];
        }

        $clauses = [];
        $depth = 0;
        $buffer = '';

        foreach ($tokens as $token) {
            if ($token === '(') {
                $depth++;
                $buffer .= $token;

                continue;
            }

            if ($token === ')') {
                $depth--;
                $buffer .= $token;

                continue;
            }

            if ($depth === 0 && strcasecmp(trim($token), 'and') === 0) {
                $clauses[] = trim($buffer);
                $buffer = '';

                continue;
            }

            $buffer .= $token;
        }

        if (trim($buffer) !== '') {
            $clauses[] = trim($buffer);
        }

        return $clauses !== [] ? $clauses : [$formula];
    }
}

<?php

namespace App\Services;

use App\Models\LessonStep;

class LessonStepMapperService
{
    public function sanitizePlainText(mixed $value, int $maxLength = 1000): string
    {
        $clean = trim(strip_tags((string) $value));
        return mb_substr($clean, 0, $maxLength);
    }

    public function sanitizeStep(array $step): array
    {
        $step['type'] = $this->sanitizePlainText($step['type'] ?? 'task', 32);
        if (($step['type'] ?? '') === 'task') {
            $step['task_type'] = $this->sanitizePlainText($step['task_type'] ?? 'multiple_choice', 64);
        }

        foreach (['title', 'name', 'question', 'sentence', 'word', 'translation', 'hint'] as $key) {
            if (isset($step[$key])) {
                $step[$key] = $this->sanitizePlainText($step[$key], 1000);
            }
        }

        if (isset($step['content']) && is_string($step['content'])) {
            $step['content'] = mb_substr(trim($step['content']), 0, 20000);
        }

        foreach (['options', 'correct_answer', 'correct_answers'] as $key) {
            if (isset($step[$key]) && is_array($step[$key])) {
                $step[$key] = array_values(array_filter(array_map(
                    fn ($v) => $this->sanitizePlainText($v, 500),
                    $step[$key]
                )));
            }
        }

        foreach (['image_url', 'audio_url', 'video_url', 'video_file_url'] as $key) {
            if (isset($step[$key])) {
                $step[$key] = mb_substr(trim((string) $step[$key]), 0, 1000);
            }
        }

        return $step;
    }

    public function mapFrontendStepToDb(array $step): array
    {
        $frontendType = (string) ($step['type'] ?? '');
        $taskType = (string) ($step['task_type'] ?? '');

        if ($frontendType === 'theory' || $frontendType === 'dialog') {
            return [
                'step_type' => 'text',
                'title' => $step['title'] ?? null,
                'prompt' => null,
                'config_json' => ['frontend_step' => $step],
            ];
        }

        $map = [
            'multiple_choice' => 'multiple_choice',
            'fill_blanks' => 'fill_gaps',
            'matching' => 'matching_pairs',
            'word_order' => 'phrase_builder',
            'audio_pick' => 'audio_choice',
            'flashcards' => 'flashcards',
        ];

        return [
            'step_type' => $map[$taskType] ?? 'quiz',
            'title' => null,
            'prompt' => $step['question'] ?? null,
            'config_json' => ['frontend_step' => $step],
        ];
    }

    public function mapDbStepToFrontend(LessonStep $step): array
    {
        $config = $step->config_json ?? [];

        if (is_array($config) && isset($config['frontend_step']) && is_array($config['frontend_step'])) {
            $frontend = $config['frontend_step'];
            if (!isset($frontend['step_id'])) {
                $frontend['step_id'] = (int) $step->id;
            }
            return $frontend;
        }

        if ($step->step_type === 'text') {
            return [
                'step_id' => (int) $step->id,
                'type' => 'theory',
                'title' => $step->title ?: 'Теория',
                'content' => (string) ($config['content'] ?? ''),
            ];
        }

        return [
            'step_id' => (int) $step->id,
            'type' => 'task',
            'task_type' => 'multiple_choice',
            'question' => (string) ($step->prompt ?? ''),
            'options' => [],
            'correct_idx' => 0,
        ];
    }
}

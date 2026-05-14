<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class QuestApiController extends Controller
{
    public function show(string $code = 'altan_zagalan'): JsonResponse
    {
        $quest = Quest::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->with([
                'nodes' => fn ($q) => $q->orderBy('order_num')->with([
                    'choices' => fn ($cq) => $cq->orderBy('order_num')->with('nextNode:id,node_key'),
                ]),
            ])
            ->first();

        if (!$quest || $quest->nodes->isEmpty()) {
            $fallbackPath = public_path('data/quest.json');
            if (File::exists($fallbackPath)) {
                return response()->json(json_decode((string) File::get($fallbackPath), true));
            }

            return response()->json([
                'start_node' => '',
                'scenes' => [],
            ]);
        }

        $startNode = (string) ($quest->nodes->first()->node_key ?? '');
        $scenes = [];

        foreach ($quest->nodes as $node) {
            $options = $node->choices->map(function ($choice): array {
                return [
                    'text' => (string) $choice->choice_text,
                    'next' => (string) ($choice->nextNode->node_key ?? ''),
                    'comment' => null,
                ];
            })->values()->all();

            $scenes[(string) $node->node_key] = [
                'speaker' => 'Рассказчик',
                'text' => (string) $node->body,
                'dictionary' => [],
                'options' => $options,
                'is_end' => count($options) === 0,
            ];
        }

        return response()->json([
            'start_node' => $startNode,
            'scenes' => $scenes,
        ]);
    }
}


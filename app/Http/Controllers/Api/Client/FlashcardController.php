<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;

class FlashcardController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'flashcards')->first();

        // ⚡ Dummy flashcards
        $flashcards = [
            ['question' => 'What is AI?', 'answer' => 'Artificial Intelligence'],
            ['question' => 'What is ML?', 'answer' => 'Machine Learning'],
        ];

        if ($tool) {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input'   => $request->topic,
                'output'  => json_encode($flashcards),
                'meta'    => json_encode([]),
            ]);
        }

        return response()->json(['flashcards' => $flashcards]);
    }
}
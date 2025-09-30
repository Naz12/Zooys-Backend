<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;

class WriterController extends Controller
{
    public function run(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'mode'   => 'nullable|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'writer')->first();

        // ⚡ Dummy writing logic
        $output = "Generated writing for: " . $request->prompt;

        if ($tool) {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input'   => $request->prompt,
                'output'  => $output,
                'meta'    => json_encode([
                    'mode' => $request->mode,
                ]),
            ]);
        }

        return response()->json(['output' => $output]);
    }
}
<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;

class DiagramController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'diagram')->first();

        // ⚡ Dummy diagram link
        $diagram = "Generated diagram for: " . $request->description;

        if ($tool) {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input'   => $request->description,
                'output'  => $diagram,
                'meta'    => json_encode([]),
            ]);
        }

        return response()->json(['diagram' => $diagram]);
    }
}
<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;

class MathController extends Controller
{
    public function solve(Request $request)
    {
        $request->validate([
            'problem' => 'required|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'math')->first();

        // ⚡ Dummy solver logic
        $solution = "Solved result for: " . $request->problem;

        if ($tool) {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input'   => $request->problem,
                'output'  => $solution,
                'meta'    => json_encode([]),
            ]);
        }

        return response()->json(['solution' => $solution]);
    }
}
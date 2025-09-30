<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;

class PdfController extends Controller
{
    public function summarize(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'pdf')->first();

        // ⚡ Dummy summary logic
        $summary = "This is a test summary for PDF: " . $request->file_path;

        if ($tool) {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input'   => $request->file_path,
                'output'  => $summary,
                'meta'    => json_encode([]),
            ]);
        }

        return response()->json(['summary' => $summary]);
    }
}
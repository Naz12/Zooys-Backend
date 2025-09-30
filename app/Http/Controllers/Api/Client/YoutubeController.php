<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;

class YoutubeController extends Controller
{
    public function summarize(Request $request)
    {
        $request->validate([
            'video_url' => 'required|url',
            'language'  => 'nullable|string',
            'mode'      => 'nullable|string',
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'youtube')->first();

        // ⚡ Dummy summary logic
        $summary = "This is a test summary for video: " . $request->video_url;

        if ($tool) {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input'   => $request->video_url,
                'output'  => $summary,
                'meta'    => json_encode([
                    'language' => $request->language,
                    'mode'     => $request->mode,
                ]),
            ]);
        }

        return response()->json(['summary' => $summary]);
    }
}
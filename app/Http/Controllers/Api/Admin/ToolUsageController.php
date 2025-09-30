<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use Illuminate\Http\Request;

class ToolUsageController extends Controller
{
    public function index()
    {
        $tools = Tool::paginate(15);

        return response()->json($tools);
    }

    public function create()
    {
        // No Blade view, just guidance
        return response()->json([
            'message' => 'Provide tool details to create.'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:tools,slug',
            'description' => 'nullable|string',
        ]);

        $tool = Tool::create($data);

        return response()->json([
            'message' => 'Tool created!',
            'tool' => $tool
        ], 201);
    }

    public function edit(Tool $tool)
    {
        return response()->json($tool);
    }

    public function update(Request $request, Tool $tool)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:tools,slug,' . $tool->id,
            'description' => 'nullable|string',
        ]);

        $tool->update($data);

        return response()->json([
            'message' => 'Tool updated!',
            'tool' => $tool
        ]);
    }

    public function destroy(Tool $tool)
    {
        $tool->delete();

        return response()->json([
            'message' => 'Tool deleted!'
        ]);
    }
}
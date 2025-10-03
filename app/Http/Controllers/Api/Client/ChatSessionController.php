<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatSessionController extends Controller
{
    /**
     * Get user's chat sessions
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $sessions = ChatSession::forUser($user->id)
                ->active()
                ->with(['lastMessage'])
                ->orderBy('updated_at', 'desc')
                ->paginate(20);
            
            return response()->json([
                'sessions' => $sessions->items(),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Chat sessions index error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to retrieve chat sessions'
            ], 500);
        }
    }

    /**
     * Create a new chat session
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            
            $session = ChatSession::create([
                'user_id' => $user->id,
                'name' => $request->input('name', 'New Chat Session'),
                'description' => $request->input('description'),
                'is_active' => true
            ]);

            return response()->json([
                'session' => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'description' => $session->description,
                    'is_active' => $session->is_active,
                    'created_at' => $session->created_at,
                    'updated_at' => $session->updated_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Chat session creation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to create chat session'
            ], 500);
        }
    }

    /**
     * Get a specific chat session
     */
    public function show(Request $request, $sessionId)
    {
        try {
            $user = $request->user();
            
            $session = ChatSession::forUser($user->id)
                ->with(['messages' => function($query) {
                    $query->orderBy('created_at', 'asc');
                }])
                ->find($sessionId);

            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            return response()->json([
                'session' => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'description' => $session->description,
                    'is_active' => $session->is_active,
                    'message_count' => $session->message_count,
                    'last_activity' => $session->last_activity,
                    'created_at' => $session->created_at,
                    'updated_at' => $session->updated_at,
                    'messages' => $session->messages->map(function($message) {
                        return [
                            'id' => $message->id,
                            'role' => $message->role,
                            'content' => $message->content,
                            'metadata' => $message->metadata,
                            'created_at' => $message->created_at
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat session show error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to retrieve chat session'
            ], 500);
        }
    }

    /**
     * Update a chat session
     */
    public function update(Request $request, $sessionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            
            $session = ChatSession::forUser($user->id)->find($sessionId);

            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            $session->update([
                'name' => $request->input('name', $session->name),
                'description' => $request->input('description', $session->description)
            ]);

            return response()->json([
                'session' => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'description' => $session->description,
                    'is_active' => $session->is_active,
                    'updated_at' => $session->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat session update error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to update chat session'
            ], 500);
        }
    }

    /**
     * Delete a chat session
     */
    public function destroy(Request $request, $sessionId)
    {
        try {
            $user = $request->user();
            
            $session = ChatSession::forUser($user->id)->find($sessionId);

            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            $session->delete();

            return response()->json([
                'message' => 'Chat session deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Chat session deletion error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to delete chat session'
            ], 500);
        }
    }

    /**
     * Archive a chat session
     */
    public function archive(Request $request, $sessionId)
    {
        try {
            $user = $request->user();
            
            $session = ChatSession::forUser($user->id)->find($sessionId);

            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            $session->update(['is_active' => false]);

            return response()->json([
                'message' => 'Chat session archived successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Chat session archive error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to archive chat session'
            ], 500);
        }
    }

    /**
     * Restore an archived chat session
     */
    public function restore(Request $request, $sessionId)
    {
        try {
            $user = $request->user();
            
            $session = ChatSession::forUser($user->id)->find($sessionId);

            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            $session->update(['is_active' => true]);

            return response()->json([
                'message' => 'Chat session restored successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Chat session restore error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to restore chat session'
            ], 500);
        }
    }
}

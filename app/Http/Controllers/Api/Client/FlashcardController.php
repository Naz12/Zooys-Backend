<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;
use App\Models\FlashcardSet;
use App\Models\Flashcard;
use App\Services\Modules\FlashcardModule;
use App\Services\Modules\UniversalFileManagementModule;
use App\Services\AIResultService;
use App\Services\UniversalJobService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FlashcardController extends Controller
{
    protected $flashcardModule;
    protected $universalFileModule;
    protected $aiResultService;
    protected $universalJobService;

    public function __construct(
        FlashcardModule $flashcardModule,
        UniversalFileManagementModule $universalFileModule,
        AIResultService $aiResultService,
        UniversalJobService $universalJobService
    ) {
        $this->flashcardModule = $flashcardModule;
        $this->universalFileModule = $universalFileModule;
        $this->aiResultService = $aiResultService;
        $this->universalJobService = $universalJobService;
    }

    /**
     * Generate flashcards asynchronously using Universal Job Scheduler
     */
    public function generate(Request $request)
    {
        $request->validate([
            'input' => 'required_without:file_id|string',
            'file_id' => 'required_without:input|string|exists:file_uploads,id',
            'input_type' => 'string|in:text,url,youtube,file',
            'count' => 'integer|min:1|max:40',
            'difficulty' => 'string|in:beginner,intermediate,advanced',
            'style' => 'string|in:definition,application,analysis,comparison,mixed',
            'model' => 'nullable|string'
        ]);

        $user = $request->user();
        $input = $request->has('input') ? trim($request->input('input')) : null;
        $inputType = $request->input('input_type', 'text');
        $fileId = $request->input('file_id');

        try {
            // Determine input type and prepare job input
            $jobInput = [
                'user_id' => $user->id
            ];
            $contentType = 'text'; // Default for status/result endpoints

            if ($fileId) {
                // File-based flashcard generation
                $jobInput['file_id'] = $fileId;
                $contentType = 'file';
                $inputType = 'file';
            } else {
                // Text/URL/YouTube-based flashcard generation
                if (empty($input)) {
                    return response()->json([
                        'error' => 'Either input or file_id is required'
                    ], 400);
                }

                // Auto-detect input type if not specified
                if ($inputType === 'text') {
                    $inputType = $this->flashcardModule->detectInputType($input);
                }

                $jobInput['input'] = $input;
                $jobInput['input_type'] = $inputType;
                
                // Map input_type to content_type for status/result endpoints
                if ($inputType === 'youtube') {
                    $contentType = 'text'; // Use 'text' for status endpoint
                } elseif ($inputType === 'url') {
                    $contentType = 'text'; // Use 'text' for status endpoint
                } else {
                    $contentType = 'text';
                }
            }

            // Prepare options
            $options = [
                'count' => $request->input('count', 5),
                'difficulty' => $request->input('difficulty', 'intermediate'),
                'style' => $request->input('style', 'mixed'),
                'model' => $request->input('model', config('services.ai_manager.default_model', 'deepseek-chat')),
                'input_type' => $inputType
            ];

            // Create universal job
            $job = $this->universalJobService->createJob('flashcards', $jobInput, $options, $user->id);

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            // Determine status/result endpoint based on content type
            $statusEndpoint = $contentType === 'file' 
                ? url('/api/status/flashcards/file?job_id=' . $job['id'])
                : url('/api/status/flashcards/text?job_id=' . $job['id']);
            
            $resultEndpoint = $contentType === 'file'
                ? url('/api/result/flashcards/file?job_id=' . $job['id'])
                : url('/api/result/flashcards/text?job_id=' . $job['id']);

            return response()->json([
                'success' => true,
                'message' => 'Flashcard generation job started',
                'job_id' => $job['id'],
                'status' => $job['status'],
                'poll_url' => $statusEndpoint,
                'result_url' => $resultEndpoint
            ], 202);

        } catch (\Exception $e) {
            Log::error('Flashcard Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to start flashcard generation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all flashcard sets for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = FlashcardSet::forUser($user->id)
            ->with('flashcards')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $flashcardSets = $query->paginate($perPage);

        return response()->json([
            'flashcard_sets' => $flashcardSets->items(),
            'pagination' => [
                'current_page' => $flashcardSets->currentPage(),
                'last_page' => $flashcardSets->lastPage(),
                'per_page' => $flashcardSets->perPage(),
                'total' => $flashcardSets->total()
            ]
        ]);
    }

    /**
     * Get a specific flashcard set with its cards
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $flashcardSet = FlashcardSet::forUser($user->id)
            ->withFlashcards()
            ->findOrFail($id);

        return response()->json([
            'flashcard_set' => $flashcardSet
        ]);
    }

    /**
     * Update a flashcard set
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean'
        ]);

        $user = $request->user();

        $flashcardSet = FlashcardSet::forUser($user->id)->findOrFail($id);

        $flashcardSet->update($request->only(['title', 'description', 'is_public']));

        return response()->json([
            'message' => 'Flashcard set updated successfully',
            'flashcard_set' => $flashcardSet->fresh()
        ]);
    }

    /**
     * Delete a flashcard set
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $flashcardSet = FlashcardSet::forUser($user->id)->findOrFail($id);

        $flashcardSet->delete();

        return response()->json([
            'message' => 'Flashcard set deleted successfully'
        ]);
    }

    /**
     * Get public flashcard sets
     */
    public function public(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = FlashcardSet::public()
            ->with(['user:id,name', 'flashcards'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $flashcardSets = $query->paginate($perPage);

        return response()->json([
            'flashcard_sets' => $flashcardSets->items(),
            'pagination' => [
                'current_page' => $flashcardSets->currentPage(),
                'last_page' => $flashcardSets->lastPage(),
                'per_page' => $flashcardSets->perPage(),
                'total' => $flashcardSets->total()
            ]
        ]);
    }

    /**
     * Save flashcard set to database
     */
    private function saveFlashcardSetToDatabase($user, $input, $inputType, $difficulty, $style, $flashcards, $sourceMetadata)
    {
        return DB::transaction(function () use ($user, $input, $inputType, $difficulty, $style, $flashcards, $sourceMetadata) {
            // Create flashcard set
            $flashcardSet = FlashcardSet::create([
                'user_id' => $user->id,
                'title' => $this->generateTitle($input, $inputType),
                'description' => $this->generateDescription($input, $inputType, $sourceMetadata),
                'input_type' => $inputType,
                'input_content' => $input,
                'difficulty' => $difficulty,
                'style' => $style,
                'total_cards' => count($flashcards),
                'source_metadata' => $sourceMetadata,
                'is_public' => false
            ]);

            // Create individual flashcards
            foreach ($flashcards as $index => $card) {
                Flashcard::create([
                    'flashcard_set_id' => $flashcardSet->id,
                    'question' => $card['question'],
                    'answer' => $card['answer'],
                    'order_index' => $index
                ]);
            }

            return $flashcardSet->load('flashcards');
        });
    }

    /**
     * Generate title for flashcard set
     */
    private function generateTitle($input, $inputType)
    {
        $maxLength = 50;
        
        switch ($inputType) {
            case 'youtube':
                return 'YouTube Video Flashcards';
            case 'url':
                return 'Web Page Flashcards';
            case 'file':
                return 'Document Flashcards';
            default:
                $title = trim($input);
                if (strlen($title) > $maxLength) {
                    $title = substr($title, 0, $maxLength) . '...';
                }
                return $title ?: 'Text Flashcards';
        }
    }

    /**
     * Generate description for flashcard set
     */
    private function generateDescription($input, $inputType, $sourceMetadata)
    {
        switch ($inputType) {
            case 'youtube':
                return "Flashcards generated from YouTube video: " . ($sourceMetadata['title'] ?? 'Unknown Video');
            case 'url':
                return "Flashcards generated from web page: " . ($sourceMetadata['title'] ?? 'Web Content');
            case 'file':
                return "Flashcards generated from document: " . ($sourceMetadata['source_type'] ?? 'File');
            default:
                return "Flashcards generated from text input";
        }
    }
}
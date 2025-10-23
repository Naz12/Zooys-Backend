<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\History;
use App\Models\FlashcardSet;
use App\Models\Flashcard;
use App\Services\Modules\ContentExtractionService;
use App\Services\FlashcardGenerationService;
use App\Services\Modules\UniversalFileManagementModule;
use App\Services\AIResultService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FlashcardController extends Controller
{
    protected $contentExtractionService;
    protected $flashcardGenerationService;
    protected $universalFileModule;
    protected $aiResultService;

    public function __construct(
        ContentExtractionService $contentExtractionService,
        FlashcardGenerationService $flashcardGenerationService,
        UniversalFileManagementModule $universalFileModule,
        AIResultService $aiResultService
    ) {
        $this->contentExtractionService = $contentExtractionService;
        $this->flashcardGenerationService = $flashcardGenerationService;
        $this->universalFileModule = $universalFileModule;
        $this->aiResultService = $aiResultService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'input' => 'required_without:file|string',
            'file' => 'required_without:input|file|max:10240',
            'input_type' => 'string|in:text,url,youtube,file',
            'count' => 'integer|min:1|max:40',
            'difficulty' => 'string|in:beginner,intermediate,advanced',
            'style' => 'string|in:definition,application,analysis,comparison,mixed'
        ]);

        $user = $request->user();
        $tool = Tool::where('slug', 'flashcards')->first();
        $input = trim($request->input);
        $inputType = $request->input('input_type', 'text');
        $count = $request->input('count', 5);
        $difficulty = $request->input('difficulty', 'intermediate');
        $style = $request->input('style', 'mixed');
        $fileUpload = null;

        try {
            // Handle file upload if provided
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $uploadResult = $this->universalFileModule->uploadFile($file, $user->id, 'flashcards', [
                    'difficulty' => $difficulty,
                    'style' => $style
                ]);

                if (!$uploadResult['success']) {
                    return response()->json([
                        'error' => $uploadResult['error']
                    ], 400);
                }

                $fileUpload = $uploadResult['file_upload'];
                $inputType = 'file';
                $input = $fileUpload->id; // Use file ID as input
            }

            // Auto-detect input type if not specified
            if ($inputType === 'text') {
                $inputType = $this->contentExtractionService->detectInputType($input);
            }

            // Validate input
            $this->contentExtractionService->validateInput($input, $inputType);

            // Extract content from input
            $extractionResult = $this->contentExtractionService->extractContent($input, $inputType);
            
            if (!$extractionResult['success']) {
                return response()->json([
                    'error' => $extractionResult['error']
                ], 400);
            }

            // Validate content for flashcard generation
            $contentValidation = $this->flashcardGenerationService->validateContent($extractionResult['content']);
            
            if (!$contentValidation['valid']) {
                return response()->json([
                    'error' => $contentValidation['error']
                ], 400);
            }

            // Generate flashcards
            $generationResult = $this->flashcardGenerationService->generateFlashcards(
                $extractionResult['content'],
                $count,
                [
                    'difficulty' => $difficulty,
                    'style' => $style
                ]
            );

            if (!$generationResult['success']) {
                return response()->json([
                    'error' => $generationResult['error']
                ], 500);
            }

            // Save flashcard set to database
            $flashcardSet = $this->saveFlashcardSetToDatabase(
                $user,
                $input,
                $inputType,
                $difficulty,
                $style,
                $generationResult['flashcards'],
                $extractionResult['metadata']
            );

            // Save AI result to database
            $aiResult = $this->aiResultService->saveResult(
                $user->id,
                'flashcards',
                $flashcardSet->title,
                $flashcardSet->description,
                [
                    'input' => $input,
                    'input_type' => $inputType,
                    'count' => $count,
                    'difficulty' => $difficulty,
                    'style' => $style
                ],
                $generationResult['flashcards'],
                array_merge($generationResult['metadata'], [
                    'input_type' => $inputType,
                    'source_metadata' => $extractionResult['metadata'],
                    'flashcard_set_id' => $flashcardSet->id
                ]),
                $fileUpload ? $fileUpload->id : null
            );

            // Store in history
        if ($tool) {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                    'input'   => $input,
                    'output'  => json_encode($generationResult['flashcards']),
                    'meta'    => json_encode([
                        'input_type' => $inputType,
                        'count' => count($generationResult['flashcards']),
                        'difficulty' => $difficulty,
                        'style' => $style,
                        'source_metadata' => $extractionResult['metadata'],
                        'flashcard_set_id' => $flashcardSet->id,
                        'ai_result_id' => $aiResult['ai_result']->id
                    ]),
                ]);
            }

            return response()->json([
                'flashcards' => $generationResult['flashcards'],
                'flashcard_set' => [
                    'id' => $flashcardSet->id,
                    'title' => $flashcardSet->title,
                    'description' => $flashcardSet->description,
                    'total_cards' => $flashcardSet->total_cards,
                    'created_at' => $flashcardSet->created_at
                ],
                'ai_result' => [
                    'id' => $aiResult['ai_result']->id,
                    'title' => $aiResult['ai_result']->title,
                    'file_url' => $aiResult['ai_result']->file_url,
                    'created_at' => $aiResult['ai_result']->created_at
                ],
                'metadata' => array_merge($generationResult['metadata'], [
                    'input_type' => $inputType,
                    'source_metadata' => $extractionResult['metadata']
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('Flashcard Generation Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'AI service is currently unavailable. Please try again later.'
            ], 503);
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
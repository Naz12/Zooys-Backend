<?php

namespace App\Services;

use App\Models\AIResult;
use App\Models\PresentationFile;
use App\Services\Modules\AIProcessingModule;
use App\Services\AIResultService;
use App\Services\Modules\ContentExtractionService;
use App\Services\UniversalJobService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AIPresentationService
{
    private $aiProcessingModule;
    private $aiResultService;
    private $contentExtractionService;
    private $universalJobService;
    private $microserviceUrl;
    private $microserviceApiKey;
    private $microserviceTimeout;

    public function __construct(
        AIProcessingModule $aiProcessingModule,
        AIResultService $aiResultService,
        ContentExtractionService $contentExtractionService,
        UniversalJobService $universalJobService
    ) {
        $this->aiProcessingModule = $aiProcessingModule;
        $this->aiResultService = $aiResultService;
        $this->contentExtractionService = $contentExtractionService;
        $this->universalJobService = $universalJobService;
        $this->microserviceUrl = env('PRESENTATION_MICROSERVICE_URL', 'http://localhost:8001');
        $this->microserviceApiKey = env('PRESENTATION_MICROSERVICE_API_KEY');
        $this->microserviceTimeout = env('PRESENTATION_MICROSERVICE_TIMEOUT', 300);
    }

    /**
     * Generate presentation outline from user input
     * Uses async job system - returns job_id for status polling
     */
    public function generateOutline($inputData, $userId)
    {
        try {
            Log::info('Starting presentation outline generation', [
                'user_id' => $userId,
                'input_type' => $inputData['input_type'] ?? 'text'
            ]);

            // Extract content based on input type
            $content = $this->extractContent($inputData);
            
            if (!$content['success']) {
                return [
                    'success' => false,
                    'error' => $content['error']
                ];
            }

            // Create job using Universal Job Scheduler
            $job = $this->universalJobService->createJob(
                'presentation_outline',
                [
                    'content' => $content['content'],
                    'input_data' => $inputData
                ],
                [],
                $userId
            );

            // Queue the job for async processing
            $this->universalJobService->queueJob($job['id']);

            return [
                'success' => true,
                'job_id' => $job['id'],
                'message' => 'Outline generation job created successfully. Use job_id to check status.'
            ];

        } catch (\Exception $e) {
            Log::error('Presentation outline generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'input_data' => $inputData
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create outline generation job: ' . $e->getMessage() . '. Please try again with the same data.'
            ];
        }
    }

    /**
     * Update presentation outline with user modifications
     */
    public function updateOutline($aiResultId, $updatedOutline, $userId)
    {
        try {
            Log::info('Updating presentation outline', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            // Use flexible lookup for public access
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Update the result data with modified outline
            $resultData = $aiResult->result_data;
            $resultData['outline'] = $updatedOutline;
            $resultData['step'] = 'outline_modified';

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'last_modified' => now()->toISOString()
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'outline' => $updatedOutline,
                    'ai_result_id' => $aiResultId
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update presentation outline', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update outline: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate full content for presentation slides
     */
    public function generateContent($aiResultId, $userId)
    {
        try {
            // For public access, try to find by ID and tool_type first, then by user_id if needed
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            $outline = $aiResult->result_data;
            $slides = $outline['slides'] ?? [];

            // Generate content for all slides in a single API call
            $contentSlides = [];
            $slidesToProcess = [];
            
            // Separate title slides from content slides
            foreach ($slides as $slide) {
                if ($slide['slide_type'] === 'title') {
                    $contentSlides[] = $slide;
                } else {
                    $slidesToProcess[] = $slide;
                }
            }
            
            // Generate content for all content slides in ONE API call
            if (!empty($slidesToProcess)) {
                $allContent = $this->generateAllSlideContent($slidesToProcess, $outline['title']);
                
                foreach ($slidesToProcess as $index => $slide) {
                    $content = $allContent[$index] ?? $this->getFallbackContent($slide);
                    $contentSlides[] = array_merge($slide, ['content' => $content]);
                }
            }

            // Update the result with full content
            $resultData = $aiResult->result_data;
            $resultData['slides'] = $contentSlides;
            $resultData['step'] = 'content_generated';

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'content_generated_at' => now()->toISOString()
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'slides' => $contentSlides,
                    'ai_result_id' => $aiResultId
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Content generation failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate content for all slides in a single API call
     */
    private function generateAllSlideContent($slides, $presentationTitle)
    {
        $slideData = [];
        foreach ($slides as $index => $slide) {
            $slideData[] = [
                'index' => $index,
                'header' => $slide['header'],
                'subheaders' => $slide['subheaders'] ?? [],
                'slide_type' => $slide['slide_type'] ?? 'content'
            ];
        }

        $prompt = "You are a professional presentation content creator. Generate high-quality, detailed content for ALL slides in this presentation titled '{$presentationTitle}'.

Complete Slide Structure:
" . json_encode($slideData, JSON_PRETTY_PRINT) . "

CRITICAL REQUIREMENTS - STRICT ENFORCEMENT:
1. ABSOLUTELY NO generic phrases like 'Important aspects', 'Current status', 'Specific examples', 'Detailed analysis', 'Practical applications', 'Measurable outcomes', 'Real-world applications', 'Industry best practices', or 'Key performance indicators'
2. Each bullet point must be 25-50 words long with substantial, meaningful content
3. Use SPECIFIC examples, statistics, case studies, or real-world applications with actual data
4. Make content directly relevant to the slide's topic and the presentation theme
5. Avoid repetitive or filler content - every bullet point must add unique value
6. Use professional business language but keep it engaging and accessible
7. Include actual numbers, percentages, or specific examples where possible
8. Each bullet point must be actionable and informative

CONTENT GUIDELINES:
- For advantages/benefits: Include specific examples, measurable outcomes, or real-world applications
- For disadvantages/challenges: Provide concrete examples, potential solutions, or mitigation strategies  
- For processes/methods: Include step-by-step details, best practices, or implementation tips
- For comparisons: Use specific criteria, examples, or data points
- For conclusions: Summarize key insights, actionable recommendations, or future implications

QUALITY STANDARDS:
- NO single-word bullet points (like 'Speed', 'Accuracy', 'Automation')
- NO generic filler phrases that could apply to any topic
- NO duplicate content across slides
- Each bullet point must be substantial and informative
- Content should demonstrate deep understanding of the topic

IMPORTANT: Generate content for ALL slides. Do not skip any slides. Each slide must have 3-5 detailed bullet points.

Format the response as a VALID JSON object with a 'slides' field containing an array of objects, each with 'index' and 'content' fields. The content should be an array of 3-5 detailed bullet points. Do not include bullet point symbols (•) in the content - just the text.

Example format:
{
  \"slides\": [
    {
      \"index\": 0,
      \"content\": [
        \"AI systems can process vast amounts of data in milliseconds, enabling real-time decision making in critical applications like autonomous vehicles and medical diagnosis\",
        \"Machine learning algorithms achieve 99.5% accuracy in image recognition tasks, significantly outperforming human capabilities in pattern detection and analysis\",
        \"Automated systems can operate 24/7 without fatigue, maintaining consistent performance levels that would be impossible for human workers to sustain\"
      ]
    }
  ]
}";

        $result = $this->aiProcessingModule->generateText($prompt);
        $response = $result['generated_content'];

        // Log the response for debugging
        Log::info('AI Content Generation Response', [
            'response_length' => strlen($response),
            'response_preview' => substr($response, 0, 200),
            'slides_count' => count($slides)
        ]);

        if (empty($response) || strpos($response, 'Sorry, I was unable') === 0) {
            Log::warning('AI content generation failed, using fallback content');
            // Return fallback content for all slides
            $fallbackContent = [];
            foreach ($slides as $index => $slide) {
                $fallbackContent[$index] = $this->getFallbackContent($slide);
            }
            return $fallbackContent;
        }

        // Try to parse JSON response
        $parsed = json_decode($response, true);
        
        // If JSON parsing fails, try to extract content from the response
        if (!$parsed || !isset($parsed['slides']) || !is_array($parsed['slides'])) {
            Log::warning('JSON parsing failed, attempting to extract content from response', [
                'response_preview' => substr($response, 0, 500)
            ]);
            
            // Try to extract content from the response even if JSON parsing fails
            $extractedContent = $this->extractContentFromResponse($response, $slides);
            if (!empty($extractedContent)) {
                Log::info('Successfully extracted content from failed JSON response');
                return $extractedContent;
            }
        } else {
            $result = [];
            $hasGenericContent = false;
            
            foreach ($parsed['slides'] as $slideContent) {
                if (isset($slideContent['index']) && isset($slideContent['content'])) {
                    // Validate content quality
                    foreach ($slideContent['content'] as $item) {
                        if ($this->containsGenericPhrase($item)) {
                            $hasGenericContent = true;
                            break 2;
                        }
                    }
                    // Add bullet points to each content item if they don't have them
                    $formattedContent = [];
                    foreach ($slideContent['content'] as $item) {
                        if (!preg_match('/^[•\-\*]\s/', $item)) {
                            $formattedContent[] = "• " . $item;
                        } else {
                            $formattedContent[] = $item;
                        }
                    }
                    $result[$slideContent['index']] = $formattedContent;
                }
            }
            
            // If generic content is found, use fallback
            if ($hasGenericContent) {
                Log::warning('Generic content detected in AI response, using fallback', [
                    'slides_count' => count($slides),
                    'result_count' => count($result)
                ]);
                $fallbackContent = [];
                foreach ($slides as $index => $slide) {
                    $fallbackContent[$index] = $this->getFallbackContent($slide);
                }
                return $fallbackContent;
            }
            
            return $result;
        }

        // Fallback if parsing fails
        Log::warning('AI response parsing failed, using fallback content', [
            'response_preview' => substr($response, 0, 300),
            'slides_count' => count($slides)
        ]);
        $fallbackContent = [];
        foreach ($slides as $index => $slide) {
            $fallbackContent[$index] = $this->getFallbackContent($slide);
        }
        return $fallbackContent;
    }

    /**
     * Extract content from AI response when JSON parsing fails
     */
    private function extractContentFromResponse($response, $slides)
    {
        $result = [];
        
        // Try to find content patterns in the response
        $lines = explode("\n", $response);
        $currentContent = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and JSON structure lines
            if (empty($line) || in_array($line, ['{', '}', '[', ']', '"slides":', '"index":', '"content":'])) {
                continue;
            }
            
            // Look for bullet points
            if (preg_match('/^[•\-\*]\s*(.+)$/', $line, $matches)) {
                $content = trim($matches[1]);
                // Remove any existing bullet points from the content
                $content = preg_replace('/^[•\-\*]\s*/', '', $content);
                if (strlen($content) > 20 && !$this->containsGenericPhrase($content)) {
                    $currentContent[] = "• " . $content;
                }
            }
            // Look for numbered items
            elseif (preg_match('/^\d+\.\s*(.+)$/', $line, $matches)) {
                $content = trim($matches[1]);
                if (strlen($content) > 20 && !$this->containsGenericPhrase($content)) {
                    $currentContent[] = "• " . $content;
                }
            }
            // Look for content in quotes
            elseif (preg_match('/^"(.+)"$/', $line, $matches)) {
                $content = trim($matches[1]);
                if (strlen($content) > 20 && !$this->containsGenericPhrase($content)) {
                    $currentContent[] = "• " . $content;
                }
            }
            // Look for any line that looks like content (not JSON structure)
            elseif (strlen($line) > 30 && 
                   !preg_match('/^[{}[\]",:]/', $line) && 
                   !preg_match('/^\d+$/', $line) &&
                   !$this->containsGenericPhrase($line)) {
                $currentContent[] = "• " . $line;
            }
        }
        
        // If we found content, distribute it across slides
        if (!empty($currentContent)) {
            $contentSlides = array_filter($slides, function($slide) {
                return $slide['slide_type'] === 'content';
            });
            
            $contentPerSlide = max(3, min(5, ceil(count($currentContent) / count($contentSlides))));
            $contentIndex = 0;
            
            foreach ($slides as $index => $slide) {
                if ($slide['slide_type'] === 'content') {
                    $slideContent = [];
                    for ($i = 0; $i < $contentPerSlide && $contentIndex < count($currentContent); $i++) {
                        $slideContent[] = $currentContent[$contentIndex];
                        $contentIndex++;
                    }
                    
                    // If we don't have enough content, use fallback for this slide
                    if (empty($slideContent)) {
                        $slideContent = $this->getFallbackContent($slide);
                    }
                    
                    $result[$index] = $slideContent;
                }
            }
        }
        
        return $result;
    }

    /**
     * Get fallback content for a slide
     */
    private function getFallbackContent($slide)
    {
        $header = $slide['header'];
        $subheaders = $slide['subheaders'] ?? [];
        
        // Create more specific fallback content based on the slide header
        $specificContent = [];
        
        // Add the subheaders as bullet points
        foreach ($subheaders as $subheader) {
            $specificContent[] = "• " . $subheader;
        }
        
        // Add topic-specific content based on common slide types
        if (stripos($header, 'history') !== false) {
            $specificContent[] = "• Historical significance and development timeline";
            $specificContent[] = "• Key events and milestones that shaped the area";
        } elseif (stripos($header, 'economic') !== false) {
            $specificContent[] = "• Economic impact and contribution to regional development";
            $specificContent[] = "• Key industries and business opportunities";
        } elseif (stripos($header, 'infrastructure') !== false) {
            $specificContent[] = "• Transportation networks and connectivity";
            $specificContent[] = "• Public services and utilities available";
        } elseif (stripos($header, 'tourist') !== false) {
            $specificContent[] = "• Popular destinations and attractions";
            $specificContent[] = "• Cultural sites and recreational activities";
        } else {
            // Generate more specific content based on the header
            $headerLower = strtolower($header);
            
            if (stripos($header, 'advantage') !== false || stripos($header, 'benefit') !== false) {
                $specificContent[] = "• Real-world case studies showing measurable improvements and success metrics";
                $specificContent[] = "• Industry-specific examples demonstrating competitive advantages and ROI";
            } elseif (stripos($header, 'disadvantage') !== false || stripos($header, 'challenge') !== false) {
                $specificContent[] = "• Documented instances where these challenges have caused significant problems";
                $specificContent[] = "• Proven strategies and best practices for overcoming these obstacles";
            } elseif (stripos($header, 'future') !== false || stripos($header, 'trend') !== false) {
                $specificContent[] = "• Current market data and projections from industry research and studies";
                $specificContent[] = "• Expert predictions and analysis of upcoming changes and opportunities";
            } elseif (stripos($header, 'conclusion') !== false || stripos($header, 'summary') !== false) {
                $specificContent[] = "• Actionable next steps with specific timelines and implementation guidelines";
                $specificContent[] = "• Key performance indicators and success metrics for measuring progress";
            } else {
                // Generate topic-specific content based on the header
                if (stripos($header, 'nurturing') !== false || stripos($header, 'care') !== false) {
                    $specificContent[] = "• Providing emotional support through active listening and empathy, creating safe spaces for expression";
                    $specificContent[] = "• Ensuring physical well-being through proper nutrition, healthcare, and safety measures";
                } elseif (stripos($header, 'development') !== false || stripos($header, 'child') !== false) {
                    $specificContent[] = "• Shaping behavioral patterns through consistent guidance, positive reinforcement, and boundary setting";
                    $specificContent[] = "• Instilling core values and ethics through daily interactions, storytelling, and leading by example";
                } elseif (stripos($header, 'work') !== false || stripos($header, 'balance') !== false) {
                    $specificContent[] = "• Managing time effectively between professional responsibilities and family commitments";
                    $specificContent[] = "• Implementing flexible scheduling, delegation strategies, and prioritizing essential tasks";
                } elseif (stripos($header, 'education') !== false || stripos($header, 'learning') !== false) {
                    $specificContent[] = "• Creating stimulating learning environments at home with educational resources and activities";
                    $specificContent[] = "• Providing homework assistance, tutoring support, and encouraging curiosity and exploration";
                } elseif (stripos($header, 'emotional') !== false || stripos($header, 'well-being') !== false) {
                    $specificContent[] = "• Establishing secure attachment bonds that provide children with confidence and emotional stability";
                    $specificContent[] = "• Offering comfort during difficult times through physical presence, reassurance, and problem-solving support";
                } elseif (stripos($header, 'cultural') !== false || stripos($header, 'social') !== false) {
                    $specificContent[] = "• Preserving family traditions, cultural heritage, and passing down stories from previous generations";
                    $specificContent[] = "• Modeling social behaviors, teaching interpersonal skills, and guiding children in community interactions";
                } elseif (stripos($header, 'support') !== false || stripos($header, 'partner') !== false) {
                    $specificContent[] = "• Collaborating with partners in decision-making, sharing parenting responsibilities, and maintaining unity";
                    $specificContent[] = "• Building strong family foundations through communication, mutual respect, and shared goals";
                } elseif (stripos($header, 'community') !== false || stripos($header, 'impact') !== false) {
                    $specificContent[] = "• Contributing to community through volunteer work, school involvement, and local initiatives";
                    $specificContent[] = "• Advocating for family-friendly policies, educational improvements, and social support systems";
                } elseif (stripos($header, 'challenge') !== false || stripos($header, 'resilience') !== false) {
                    $specificContent[] = "• Demonstrating perseverance through difficult circumstances and modeling problem-solving approaches";
                    $specificContent[] = "• Adapting to changing family dynamics, economic conditions, and evolving child development needs";
                } elseif (stripos($header, 'celebrat') !== false || stripos($header, 'acknowledge') !== false) {
                    $specificContent[] = "• Recognizing the countless daily contributions that often go unnoticed but are essential for family functioning";
                    $specificContent[] = "• Expressing appreciation for sacrifices made, time invested, and unconditional love provided";
                } elseif (stripos($header, 'conclusion') !== false || stripos($header, 'summary') !== false) {
                    $specificContent[] = "• Summarizing the multifaceted role mothers play in shaping individuals, families, and society";
                    $specificContent[] = "• Highlighting the importance of supporting mothers through policies, community resources, and recognition";
                } else {
                    // Generate topic-specific content based on the header
                    $headerLower = strtolower($header);
                    
                    if (stripos($header, 'evolution') !== false || stripos($header, 'timeline') !== false) {
                        $specificContent[] = "• Detailed chronological progression showing key developments and milestones in the evolutionary process";
                        $specificContent[] = "• Analysis of significant events and their impact on the overall development and transformation";
                    } elseif (stripos($header, 'theory') !== false || stripos($header, 'theories') !== false) {
                        $specificContent[] = "• Comprehensive examination of different theoretical frameworks and their supporting evidence";
                        $specificContent[] = "• Comparative analysis of competing theories and their implications for understanding the topic";
                    } elseif (stripos($header, 'genetic') !== false || stripos($header, 'dna') !== false) {
                        $specificContent[] = "• Scientific research findings and genetic analysis providing insights into biological relationships";
                        $specificContent[] = "• Molecular evidence and genetic markers that support current understanding of the subject";
                    } elseif (stripos($header, 'civilization') !== false || stripos($header, 'ancient') !== false) {
                        $specificContent[] = "• Archaeological discoveries and historical evidence of early human settlements and development";
                        $specificContent[] = "• Cultural and technological advancements that shaped early human societies and communities";
                    } elseif (stripos($header, 'climate') !== false || stripos($header, 'environment') !== false) {
                        $specificContent[] = "• Environmental factors and climatic changes that influenced human adaptation and survival strategies";
                        $specificContent[] = "• Impact of natural conditions on human behavior, migration patterns, and cultural development";
                    } elseif (stripos($header, 'cultural') !== false || stripos($header, 'society') !== false) {
                        $specificContent[] = "• Social structures and cultural practices that evolved within human communities over time";
                        $specificContent[] = "• Transmission of knowledge, traditions, and innovations across generations and regions";
                    } elseif (stripos($header, 'diversity') !== false || stripos($header, 'modern') !== false) {
                        $specificContent[] = "• Contemporary variations and differences observed in modern human populations and cultures";
                        $specificContent[] = "• Current challenges and opportunities facing diverse human communities in the modern world";
                    } elseif (stripos($header, 'ethical') !== false || stripos($header, 'consideration') !== false) {
                        $specificContent[] = "• Moral implications and ethical considerations surrounding research and understanding of human origins";
                        $specificContent[] = "• Respect for human dignity and cultural sensitivity in scientific investigation and interpretation";
                    } elseif (stripos($header, 'market') !== false || stripos($header, 'analysis') !== false) {
                        $specificContent[] = "• Current market conditions and trends affecting the industry and business environment";
                        $specificContent[] = "• Competitive landscape analysis and strategic positioning within the marketplace";
                    } elseif (stripos($header, 'design') !== false || stripos($header, 'technical') !== false) {
                        $specificContent[] = "• Innovative design elements and technical specifications that enhance functionality and performance";
                        $specificContent[] = "• User experience considerations and engineering solutions that improve product quality";
                    } elseif (stripos($header, 'performance') !== false || stripos($header, 'review') !== false) {
                        $specificContent[] = "• Performance metrics and testing results demonstrating effectiveness and reliability";
                        $specificContent[] = "• Customer feedback and evaluation data showing satisfaction levels and areas for improvement";
                    } elseif (stripos($header, 'marketing') !== false || stripos($header, 'strategy') !== false) {
                        $specificContent[] = "• Strategic marketing approaches and promotional campaigns targeting specific audience segments";
                        $specificContent[] = "• Brand positioning and communication strategies that effectively reach and engage customers";
                    } elseif (stripos($header, 'sales') !== false || stripos($header, 'projection') !== false) {
                        $specificContent[] = "• Sales forecasting and revenue projections based on market analysis and historical data";
                        $specificContent[] = "• Financial targets and growth strategies for achieving business objectives and market expansion";
                    } elseif (stripos($header, 'success') !== false || stripos($header, 'story') !== false) {
                        $specificContent[] = "• Real-world examples and case studies demonstrating successful implementation and positive outcomes";
                        $specificContent[] = "• Testimonials and success stories from satisfied customers and stakeholders";
                    } elseif (stripos($header, 'future') !== false || stripos($header, 'development') !== false) {
                        $specificContent[] = "• Upcoming features and planned improvements that will enhance functionality and user experience";
                        $specificContent[] = "• Long-term development roadmap and expansion opportunities for continued growth";
                    } elseif (stripos($header, 'competitive') !== false || stripos($header, 'advantage') !== false) {
                        $specificContent[] = "• Unique selling propositions and competitive advantages that differentiate from market alternatives";
                        $specificContent[] = "• Market positioning and value propositions that create sustainable competitive advantage";
                    } else {
                        // Generic but specific content for unknown topics
                        $specificContent[] = "• Comprehensive analysis of key concepts and their practical applications in real-world scenarios";
                        $specificContent[] = "• Examination of current trends, challenges, and opportunities within this specific domain";
                    }
                }
            }
        }
        
        return $specificContent;
    }

    /**
     * Check if content contains generic phrases
     */
    private function containsGenericPhrase($content)
    {
        $genericPhrases = [
            'Important aspects and key features',
            'Current status and future potential',
            'Specific examples and real-world applications',
            'Measurable outcomes and performance improvements',
            'Detailed analysis and comprehensive coverage',
            'Practical applications and implementation considerations',
            'Industry best practices and proven methodologies',
            'Success metrics and key performance indicators',
            'Emerging trends and developments',
            'Predicted impact and implications',
            'Key insights and actionable recommendations',
            'Next steps and future considerations'
        ];
        
        foreach ($genericPhrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate content for a single slide using OpenAI
     */
    private function generateSlideContent($slide, $presentationTitle)
    {
        $header = $slide['header'];
        $subheaders = $slide['subheaders'] ?? [];
        $slideType = $slide['slide_type'] ?? 'content';

        $prompt = "Generate detailed, specific content for a presentation slide about '{$header}' in a presentation titled '{$presentationTitle}'.

Slide Type: {$slideType}
Key Points to Cover: " . implode(', ', $subheaders) . "

IMPORTANT: Generate REAL, SPECIFIC content about the topic. Do NOT use generic placeholder text like 'Additional details and insights' or 'Key takeaways and important information'.

Please provide:
1. 4-6 detailed bullet points with SPECIFIC information about the topic
2. Real facts, examples, statistics, or concrete details where relevant
3. Professional, engaging content suitable for a business presentation
4. Each bullet point should be informative and specific (2-3 sentences)
5. Focus on the actual topic - be specific and factual

Format the response as a JSON object with a 'content' field containing an array of bullet points.";

        $result = $this->aiProcessingModule->generateText($prompt);
        $response = $result['generated_content'];

        if (empty($response) || strpos($response, 'Sorry, I was unable') === 0) {
            // Fallback content if OpenAI fails
            return [
                "• " . implode("\n• ", $subheaders),
                "• Additional details and insights about {$header}",
                "• Key takeaways and important information",
                "• Professional presentation content"
            ];
        }

        // Try to parse JSON response
        $parsed = json_decode($response, true);
        if ($parsed && isset($parsed['content']) && is_array($parsed['content'])) {
            return $parsed['content'];
        }

        // If not JSON, split by lines and format as bullet points
        $lines = array_filter(array_map('trim', explode("\n", $response)));
        $bulletPoints = [];
        
        foreach ($lines as $line) {
            if (!empty($line)) {
                // Add bullet point if not already present
                $bulletPoints[] = (strpos($line, '•') === 0 || strpos($line, '-') === 0) ? $line : "• " . $line;
            }
        }

        return !empty($bulletPoints) ? $bulletPoints : [
            "• " . implode("\n• ", $subheaders),
            "• Additional details and insights about {$header}",
            "• Key takeaways and important information"
        ];
    }


    /**
     * Extract content based on input type
     */
    private function extractContent($inputData)
    {
        $inputType = $inputData['input_type'] ?? 'text';

        try {
            switch ($inputType) {
                case 'text':
                    return [
                        'success' => true,
                        'content' => $inputData['topic'] ?? $inputData['content']
                    ];

                case 'file':
                    $extractionResult = $this->contentExtractionService->extractContent(
                        $inputData['file_path'],
                        'document',
                        ['file_type' => $inputData['file_type'] ?? 'pdf']
                    );
                    return $extractionResult;

                case 'url':
                    $extractionResult = $this->contentExtractionService->extractContent(
                        $inputData['url'],
                        'url'
                    );
                    return $extractionResult;

                case 'youtube':
                    $extractionResult = $this->contentExtractionService->extractContent(
                        $inputData['youtube_url'],
                        'youtube'
                    );
                    return $extractionResult;

                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported input type: ' . $inputType
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Content extraction failed', [
                'error' => $e->getMessage(),
                'input_type' => $inputType
            ]);

            return [
                'success' => false,
                'error' => 'Failed to extract content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Call microservice for outline generation
     */
    private function callMicroserviceForOutline($content, $inputData)
    {
        try {
            $language = $inputData['language'] ?? 'English';
            $tone = $inputData['tone'] ?? 'Professional';
            $length = $inputData['length'] ?? 'Medium';
            $model = $inputData['model'] ?? null;

            // Prepare request data for microservice (API v3.1.0)
            $requestData = [
                'content' => $content,
                'language' => $language,
                'tone' => $tone,
                'length' => $length
            ];
            
            // Only include model if provided (defaults to deepseek-chat in microservice)
            if ($model) {
                $requestData['model'] = $model;
            }

            Log::info('Calling microservice for outline generation', [
                'url' => $this->microserviceUrl . '/generate-outline',
                'request_data' => $requestData,
                'content_length' => strlen($content)
            ]);

            // Call microservice
            $response = $this->callMicroservice($this->microserviceUrl . '/generate-outline', $requestData);

            // Validate response is an array
            if (!is_array($response)) {
                Log::error('Microservice returned invalid response type', [
                    'response_type' => gettype($response),
                    'response' => $response
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid response from microservice'
                ];
            }

            Log::info('Microservice response received', [
                'success' => $response['success'] ?? false,
                'response_keys' => is_array($response) ? array_keys($response) : [],
                'error' => $response['error'] ?? null
            ]);

            if (!isset($response['success']) || !$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Microservice call failed'
                ];
            }

            // Check if data exists
            if (!isset($response['data'])) {
                Log::error('Microservice response missing data', [
                    'response' => $response
                ]);
                return [
                    'success' => false,
                    'error' => 'Microservice response missing data'
                ];
            }

            return [
                'success' => true,
                'data' => $response['data']
            ];

        } catch (\Exception $e) {
            Log::error('Microservice outline generation failed', [
                'error' => $e->getMessage(),
                'content_length' => strlen($content),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate outline: ' . $e->getMessage()
            ];
        }
    }


    /**
     * Call microservice for content generation
     */
    public function callMicroserviceForContent($outline, $language = 'English', $tone = 'Professional', $detailLevel = 'detailed')
    {
        try {
            // Prepare request data for microservice (API v3.1.0)
            $requestData = [
                'outline' => $outline,
                'language' => $language,
                'tone' => $tone,
                'detail_level' => $detailLevel
            ];
            
            // Model parameter is optional and defaults to deepseek-chat in microservice

            // Call microservice
            $response = $this->callMicroservice($this->microserviceUrl . '/generate-content', $requestData);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Microservice call failed'
                ];
            }

            return [
                'success' => true,
                'data' => $response['data']
            ];

        } catch (\Exception $e) {
            Log::error('Microservice content generation failed', [
                'error' => $e->getMessage(),
                'outline_title' => $outline['title'] ?? 'Unknown'
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate full presentation content from outline
     * Uses async job system - returns job_id for status polling
     */
    public function generateFullContent($outline, $userId, $language = 'English', $tone = 'Professional', $detailLevel = 'detailed')
    {
        try {
            Log::info('Starting full content generation', [
                'user_id' => $userId,
                'language' => $language,
                'tone' => $tone
            ]);

            // Validate outline structure
            $validation = $this->validateOutlineStructure($outline);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => 'Invalid outline structure: ' . $validation['error']
                ];
            }

            // Create job using Universal Job Scheduler
            $job = $this->universalJobService->createJob(
                'presentation_content',
                [
                    'outline' => $outline,
                    'language' => $language,
                    'tone' => $tone,
                    'detail_level' => $detailLevel
                ],
                [],
                $userId
            );

            // Queue the job for async processing
            $this->universalJobService->queueJob($job['id']);

            return [
                'success' => true,
                'job_id' => $job['id'],
                'message' => 'Content generation job created successfully. Use job_id to check status.'
            ];

        } catch (\Exception $e) {
            Log::error('Full content generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create content generation job: ' . $e->getMessage() . '. Please try again with the same data.'
            ];
        }
    }

    /**
     * Export presentation to PowerPoint using Universal Job Scheduler
     * Accepts content + style from frontend, validates, processes async, saves file
     */
    public function exportPresentation($presentationData, $userId, $templateData = null)
    {
        try {
            Log::info('Starting presentation export', [
                'user_id' => $userId
            ]);

            // Validate content structure
            $validation = $this->validateContentStructure($presentationData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => 'Invalid content structure: ' . $validation['error']
                ];
            }

            // Create job using Universal Job Scheduler
            $job = $this->universalJobService->createJob(
                'presentation_export',
                [
                    'presentation_data' => $presentationData,
                    'template_data' => $templateData
                ],
                [],
                $userId
            );

            // Queue the job for async processing
            $this->universalJobService->queueJob($job['id']);

            return [
                'success' => true,
                'job_id' => $job['id'],
                'message' => 'Export job created successfully. Use job_id to check status.'
            ];

        } catch (\Exception $e) {
            Log::error('Export job creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create export job: ' . $e->getMessage() . '. Please try again with the same data.'
            ];
        }
    }

    /**
     * Process presentation outline generation job (called by Universal Job Scheduler)
     */
    public function processOutlineJob($jobId, $job)
    {
        try {
            $this->universalJobService->updateJob($jobId, [
                'stage' => 'extracting_content',
                'progress' => 10
            ]);

            $content = $job['input']['content'];
            $inputData = $job['input']['input_data'];
            $userId = $job['user_id'];

            $language = $inputData['language'] ?? 'English';
            $tone = $inputData['tone'] ?? 'Professional';
            $length = $inputData['length'] ?? 'Medium';
            $model = $inputData['model'] ?? null;

            $this->universalJobService->updateJob($jobId, [
                'stage' => 'submitting_to_microservice',
                'progress' => 20
            ]);

            // Prepare request data for microservice
            $requestData = [
                'content' => $content,
                'language' => $language,
                'tone' => $tone,
                'length' => $length
            ];
            
            if ($model) {
                $requestData['model'] = $model;
            }

            // Submit job to microservice (async)
            $microserviceResponse = $this->callMicroservice($this->microserviceUrl . '/generate-outline', $requestData, true);

            if (!$microserviceResponse['success'] || !isset($microserviceResponse['job_id'])) {
                $this->universalJobService->failJob($jobId, $microserviceResponse['error'] ?? 'Failed to submit job to microservice');
                return ['success' => false, 'error' => $microserviceResponse['error'] ?? 'Failed to submit job to microservice'];
            }

            $microserviceJobId = $microserviceResponse['job_id'];

            $this->universalJobService->updateJob($jobId, [
                'stage' => 'polling_microservice',
                'progress' => 30
            ]);

            // Poll microservice for completion
            $maxAttempts = 120; // 4 minutes max (120 * 2 seconds)
            $intervalSeconds = 2;

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                // Memory cleanup
                if ($attempt % 10 === 0 && function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }

                $statusResponse = $this->getJobStatus($microserviceJobId);
                
                if (!$statusResponse['success']) {
                    $this->universalJobService->failJob($jobId, 'Failed to get microservice job status: ' . ($statusResponse['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => $statusResponse['error'] ?? 'Failed to get job status'];
                }

                // Validate response structure
                if (!isset($statusResponse['data']) || !is_array($statusResponse['data'])) {
                    $this->universalJobService->failJob($jobId, 'Invalid microservice response structure');
                    return ['success' => false, 'error' => 'Invalid microservice response structure'];
                }

                $statusData = $statusResponse['data'];
                $status = $statusData['status'] ?? 'unknown';
                $progress = $statusData['progress'] ?? 0;

                // Break on invalid status after a few attempts
                if ($status === 'unknown' && $attempt > 5) {
                    $this->universalJobService->failJob($jobId, 'Microservice returned unknown status repeatedly');
                    return ['success' => false, 'error' => 'Microservice returned unknown status'];
                }

                // Update progress based on microservice progress
                $laravelProgress = 30 + (int)($progress * 0.6); // 30-90% range
                $this->universalJobService->updateJob($jobId, [
                    'stage' => 'generating_outline',
                    'progress' => $laravelProgress
                ]);

                if ($status === 'completed') {
                    // Get result from microservice
                    $resultResponse = $this->getJobResult($microserviceJobId);
                    
                    if (!$resultResponse['success']) {
                        $this->universalJobService->failJob($jobId, 'Failed to get microservice job result: ' . ($resultResponse['error'] ?? 'Unknown error'));
                        return ['success' => false, 'error' => $resultResponse['error'] ?? 'Failed to get job result'];
                    }

                    $outlineData = $resultResponse['data']['data'] ?? $resultResponse['data'] ?? null;

                    if (!$outlineData) {
                        $this->universalJobService->failJob($jobId, 'Microservice result missing outline data');
                        return ['success' => false, 'error' => 'Result missing outline data'];
                    }

                    $this->universalJobService->completeJob($jobId, [
                        'outline' => $outlineData
                    ], [
                        'microservice_job_id' => $microserviceJobId,
                        'language' => $language,
                        'tone' => $tone,
                        'length' => $length
                    ]);

                    return [
                        'success' => true,
                        'data' => ['outline' => $outlineData]
                    ];
                } elseif ($status === 'failed') {
                    $errorMessage = $statusData['error'] ?? $statusData['message'] ?? 'Microservice job failed';
                    $this->universalJobService->failJob($jobId, $errorMessage);
                    return ['success' => false, 'error' => $errorMessage];
                }

                // Wait before next poll
                sleep($intervalSeconds);
            }

            // Timeout
            $this->universalJobService->failJob($jobId, 'Outline generation timed out after ' . ($maxAttempts * $intervalSeconds) . ' seconds');
            return ['success' => false, 'error' => 'Job timed out'];

        } catch (\Exception $e) {
            Log::error('Outline job processing failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);

            $this->universalJobService->failJob($jobId, 'Outline generation failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process presentation content generation job (called by Universal Job Scheduler)
     */
    public function processContentJob($jobId, $job)
    {
        try {
            $this->universalJobService->updateJob($jobId, [
                'stage' => 'validating',
                'progress' => 10
            ]);

            $outline = $job['input']['outline'];
            $language = $job['input']['language'] ?? 'English';
            $tone = $job['input']['tone'] ?? 'Professional';
            $detailLevel = $job['input']['detail_level'] ?? 'detailed';
            $userId = $job['user_id'];

            // Validate outline structure
            $validation = $this->validateOutlineStructure($outline);
            if (!$validation['valid']) {
                $this->universalJobService->failJob($jobId, 'Invalid outline structure: ' . $validation['error']);
                return ['success' => false, 'error' => $validation['error']];
            }

            $this->universalJobService->updateJob($jobId, [
                'stage' => 'submitting_to_microservice',
                'progress' => 20
            ]);

            // Prepare request data for microservice
            $requestData = [
                'outline' => $outline,
                'language' => $language,
                'tone' => $tone,
                'detail_level' => $detailLevel
            ];

            // Submit job to microservice (async)
            $microserviceResponse = $this->callMicroservice($this->microserviceUrl . '/generate-content', $requestData, true);

            if (!$microserviceResponse['success'] || !isset($microserviceResponse['job_id'])) {
                $this->universalJobService->failJob($jobId, $microserviceResponse['error'] ?? 'Failed to submit job to microservice');
                return ['success' => false, 'error' => $microserviceResponse['error'] ?? 'Failed to submit job to microservice'];
            }

            $microserviceJobId = $microserviceResponse['job_id'];

            $this->universalJobService->updateJob($jobId, [
                'stage' => 'polling_microservice',
                'progress' => 30
            ]);

            // Poll microservice for completion
            $maxAttempts = 120; // 4 minutes max
            $intervalSeconds = 2;

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                // Memory cleanup
                if ($attempt % 10 === 0 && function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }

                $statusResponse = $this->getJobStatus($microserviceJobId);
                
                if (!$statusResponse['success']) {
                    $this->universalJobService->failJob($jobId, 'Failed to get microservice job status: ' . ($statusResponse['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => $statusResponse['error'] ?? 'Failed to get job status'];
                }

                // Validate response structure
                if (!isset($statusResponse['data']) || !is_array($statusResponse['data'])) {
                    $this->universalJobService->failJob($jobId, 'Invalid microservice response structure');
                    return ['success' => false, 'error' => 'Invalid microservice response structure'];
                }

                $statusData = $statusResponse['data'];
                $status = $statusData['status'] ?? 'unknown';
                $progress = $statusData['progress'] ?? 0;

                // Break on invalid status after a few attempts
                if ($status === 'unknown' && $attempt > 5) {
                    $this->universalJobService->failJob($jobId, 'Microservice returned unknown status repeatedly');
                    return ['success' => false, 'error' => 'Microservice returned unknown status'];
                }

                // Update progress based on microservice progress
                $laravelProgress = 30 + (int)($progress * 0.6); // 30-90% range
                $this->universalJobService->updateJob($jobId, [
                    'stage' => 'generating_content',
                    'progress' => $laravelProgress
                ]);

                if ($status === 'completed') {
                    // Get result from microservice
                    $resultResponse = $this->getJobResult($microserviceJobId);
                    
                    if (!$resultResponse['success']) {
                        $this->universalJobService->failJob($jobId, 'Failed to get microservice job result: ' . ($resultResponse['error'] ?? 'Unknown error'));
                        return ['success' => false, 'error' => $resultResponse['error'] ?? 'Failed to get job result'];
                    }

                    $contentData = $resultResponse['data']['data'] ?? $resultResponse['data'] ?? null;

                    if (!$contentData) {
                        $this->universalJobService->failJob($jobId, 'Microservice result missing content data');
                        return ['success' => false, 'error' => 'Result missing content data'];
                    }

                    $this->universalJobService->completeJob($jobId, [
                        'content' => $contentData
                    ], [
                        'microservice_job_id' => $microserviceJobId,
                        'language' => $language,
                        'tone' => $tone,
                        'detail_level' => $detailLevel
                    ]);

                    return [
                        'success' => true,
                        'data' => ['content' => $contentData]
                    ];
                } elseif ($status === 'failed') {
                    $errorMessage = $statusData['error'] ?? $statusData['message'] ?? 'Microservice job failed';
                    $this->universalJobService->failJob($jobId, $errorMessage);
                    return ['success' => false, 'error' => $errorMessage];
                }

                // Wait before next poll
                sleep($intervalSeconds);
            }

            // Timeout
            $this->universalJobService->failJob($jobId, 'Content generation timed out after ' . ($maxAttempts * $intervalSeconds) . ' seconds');
            return ['success' => false, 'error' => 'Job timed out'];

        } catch (\Exception $e) {
            Log::error('Content job processing failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);

            $this->universalJobService->failJob($jobId, 'Content generation failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process presentation export job (called by Universal Job Scheduler)
     */
    public function processExportJob($jobId, $job)
    {
        try {
            $this->universalJobService->updateJob($jobId, [
                'stage' => 'validating',
                'progress' => 10
            ]);

            $presentationData = $job['input']['presentation_data'];
            $templateData = $job['input']['template_data'] ?? null;
            $userId = $job['user_id'];

            // Transform content format if needed (convert string content to array)
            $presentationData = $this->transformContentForExport($presentationData);

            // Validate content structure
            $validation = $this->validateContentStructure($presentationData);
            if (!$validation['valid']) {
                $errorMessage = is_array($validation['error']) ? json_encode($validation['error']) : (string)$validation['error'];
                $this->universalJobService->failJob($jobId, 'Invalid content structure: ' . $errorMessage);
                return ['success' => false, 'error' => $errorMessage];
            }

            $this->universalJobService->updateJob($jobId, [
                'stage' => 'calling_microservice',
                'progress' => 30
            ]);

            // Prepare data for microservice - content can be string or array
            // The microservice accepts content as string in slides
            $slides = [];
            foreach ($presentationData['slides'] as $index => $slide) {
                // Content can be string or array - microservice accepts both
                $content = $slide['content'] ?? '';
                
                // If content is an array, convert to string (join with newlines)
                if (is_array($content)) {
                    $content = implode("\n", $content);
                } elseif (!is_string($content)) {
                    $content = (string)$content;
                }
                
                $processedSlide = [
                    'slide_number' => $slide['slide_number'] ?? ($index + 1),
                    'header' => $slide['header'] ?? '',
                    'subheaders' => is_array($slide['subheaders'] ?? null) ? $slide['subheaders'] : [],
                    'slide_type' => $slide['slide_type'] ?? 'content',
                    'content' => $content
                ];
                $slides[] = $processedSlide;
            }

            // Microservice expects 'content' root key, not 'presentation_data'
            $requestData = [
                'content' => [
                    'title' => (string)($presentationData['title'] ?? 'Presentation'),
                    'slides' => $slides
                ],
                'random_id' => (string)$jobId, // Use Zooys job ID as random_id
                'user_id' => (int)$userId,
                'template' => (string)($templateData['template'] ?? 'corporate_blue'),
                'color_scheme' => (string)($templateData['color_scheme'] ?? 'blue'),
                'font_style' => (string)($templateData['font_style'] ?? 'modern')
            ];

            // Validate all data types before sending
            try {
                // Test JSON encoding to catch any array-to-string issues
                $testJson = json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON encoding failed: ' . json_last_error_msg());
                }
                
                Log::info('Prepared export request data', [
                    'slides_count' => count($slides),
                    'first_slide_content_type' => gettype($slides[0]['content'] ?? null),
                    'first_slide_content_is_array' => is_array($slides[0]['content'] ?? null),
                    'json_encode_success' => true
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to prepare export request data', [
                    'error' => $e->getMessage(),
                    'slides_count' => count($slides)
                ]);
                $this->universalJobService->failJob($jobId, 'Failed to prepare export data: ' . $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }

            // Submit job to microservice (async)
            $microserviceResponse = $this->callMicroservice($this->microserviceUrl . '/export', $requestData, true);

            if (!$microserviceResponse['success'] || !isset($microserviceResponse['job_id'])) {
                $errorMsg = $microserviceResponse['error'] ?? 'Failed to submit job to microservice';
                $errorMsg = is_array($errorMsg) ? json_encode($errorMsg) : (string)$errorMsg;
                $this->universalJobService->failJob($jobId, $errorMsg);
                return ['success' => false, 'error' => $errorMsg];
            }

            $microserviceJobId = $microserviceResponse['job_id'];

            $this->universalJobService->updateJob($jobId, [
                'stage' => 'polling_microservice',
                'progress' => 40
            ]);

            // Poll microservice for completion
            $maxAttempts = 180; // 6 minutes max (180 * 2 seconds) - export takes longer
            $intervalSeconds = 2;

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                // Memory cleanup
                if ($attempt % 10 === 0 && function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }

                $statusResponse = $this->getJobStatus($microserviceJobId);
                
                if (!$statusResponse['success']) {
                    $this->universalJobService->failJob($jobId, 'Failed to get microservice job status: ' . ($statusResponse['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => $statusResponse['error'] ?? 'Failed to get job status'];
                }

                // Validate response structure
                if (!isset($statusResponse['data']) || !is_array($statusResponse['data'])) {
                    $this->universalJobService->failJob($jobId, 'Invalid microservice response structure');
                    return ['success' => false, 'error' => 'Invalid microservice response structure'];
                }

                $statusData = $statusResponse['data'];
                $status = $statusData['status'] ?? 'unknown';
                $progress = $statusData['progress'] ?? 0;

                // Break on invalid status after a few attempts
                if ($status === 'unknown' && $attempt > 5) {
                    $this->universalJobService->failJob($jobId, 'Microservice returned unknown status repeatedly');
                    return ['success' => false, 'error' => 'Microservice returned unknown status'];
                }

                // Update progress based on microservice progress
                $laravelProgress = 40 + (int)($progress * 0.4); // 40-80% range
                $this->universalJobService->updateJob($jobId, [
                    'stage' => 'generating_pptx',
                    'progress' => $laravelProgress
                ]);

                if ($status === 'completed') {
                    // Get result from microservice
                    $resultResponse = $this->getJobResult($microserviceJobId);
                    
                    if (!$resultResponse['success']) {
                        $this->universalJobService->failJob($jobId, 'Failed to get microservice job result: ' . ($resultResponse['error'] ?? 'Unknown error'));
                        return ['success' => false, 'error' => $resultResponse['error'] ?? 'Failed to get job result'];
                    }

                    $exportData = $resultResponse['data']['data'] ?? $resultResponse['data'] ?? null;

                    if (!$exportData || !isset($exportData['file_content'])) {
                        $this->universalJobService->failJob($jobId, 'Microservice result missing file content');
                        return ['success' => false, 'error' => 'Result missing file content'];
                    }

                    $this->universalJobService->updateJob($jobId, [
                        'stage' => 'saving_file',
                        'progress' => 85
                    ]);

                    // Save file
                    $fileResult = $this->savePresentationFile(
                        $userId,
                        $presentationData['title'],
                        $exportData,
                        $templateData,
                        $presentationData // Store original content data for editing
                    );

                    if (!$fileResult['success']) {
                        $this->universalJobService->failJob($jobId, $fileResult['error']);
                        return ['success' => false, 'error' => $fileResult['error']];
                    }

                    $this->universalJobService->completeJob($jobId, [
                        'file_id' => $fileResult['file_id'],
                        'filename' => $fileResult['filename'],
                        'download_url' => $fileResult['download_url'],
                        'file_size' => $fileResult['file_size'],
                        'slides_count' => $fileResult['slides_count']
                    ], [
                        'microservice_job_id' => $microserviceJobId,
                        'file_size' => $fileResult['file_size'],
                        'slides_count' => $fileResult['slides_count']
                    ]);

                    return [
                        'success' => true,
                        'data' => $fileResult
                    ];
                } elseif ($status === 'failed') {
                    $errorMessage = $statusData['error'] ?? $statusData['message'] ?? 'Microservice job failed';
                    $this->universalJobService->failJob($jobId, $errorMessage);
                    return ['success' => false, 'error' => $errorMessage];
                }

                // Wait before next poll
                sleep($intervalSeconds);
            }

            // Timeout
            $this->universalJobService->failJob($jobId, 'Export timed out after ' . ($maxAttempts * $intervalSeconds) . ' seconds');
            return ['success' => false, 'error' => 'Job timed out'];

        } catch (\Exception $e) {
            Log::error('Export job processing failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Export failed: ' . $e->getMessage();
            $this->universalJobService->failJob($jobId, $errorMessage);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Save presentation file to storage and database
     * @param int $userId
     * @param string $title
     * @param array $microserviceResponse
     * @param array|null $templateData
     * @param array|null $contentData The original content data for editing purposes
     */
    private function savePresentationFile($userId, $title, $microserviceResponse, $templateData = null, $contentData = null)
    {
        try {
            // Decode base64 file content
            $fileContent = base64_decode($microserviceResponse['file_content']);
            $filename = $microserviceResponse['filename'] ?? 'presentation_' . Str::uuid() . '.pptx';
            $fileSize = $microserviceResponse['file_size'] ?? strlen($fileContent);
            $slidesCount = $microserviceResponse['metadata']['slides_count'] ?? 0;

            // Create user-specific folder
            $userFolder = 'presentations/' . $userId;
            $filePath = $userFolder . '/' . $filename;

            // Ensure directory exists
            Storage::disk('public')->makeDirectory($userFolder);

            // Save file
            Storage::disk('public')->put($filePath, $fileContent);

            // Create database record
            $presentationFile = PresentationFile::create([
                'user_id' => $userId,
                'title' => $title,
                'filename' => $filename,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'template' => $templateData['template'] ?? null,
                'color_scheme' => $templateData['color_scheme'] ?? null,
                'font_style' => $templateData['font_style'] ?? null,
                'slides_count' => $slidesCount,
                'metadata' => [
                    'exported_at' => now()->toISOString(),
                    'exported_by' => 'fastapi_microservice'
                ],
                'content_data' => $contentData, // Store original content for editing
                'expires_at' => now()->addMonth() // Auto-delete after 1 month
            ]);

            return [
                'success' => true,
                'file_id' => $presentationFile->id,
                'filename' => $filename,
                'download_url' => $presentationFile->file_url,
                'file_size' => $fileSize,
                'slides_count' => $slidesCount
            ];

        } catch (\Exception $e) {
            Log::error('Save presentation file failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to save file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all presentation files for a user
     */
    public function getUserPresentationFiles($userId, $perPage = 15, $search = null)
    {
        $query = PresentationFile::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('filename', 'like', "%{$search}%");
            });
        }

        $files = $query->paginate($perPage);

        return [
            'success' => true,
            'data' => [
                'files' => $files->items(),
                'pagination' => [
                    'current_page' => $files->currentPage(),
                    'last_page' => $files->lastPage(),
                    'per_page' => $files->perPage(),
                    'total' => $files->total()
                ]
            ]
        ];
    }

    /**
     * Delete a presentation file
     */
    public function deletePresentationFile($fileId, $userId)
    {
        try {
            $file = PresentationFile::where('id', $fileId)
                ->where('user_id', $userId)
                ->first();

            if (!$file) {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }

            $file->delete(); // This will also delete the physical file via model boot

            return [
                'success' => true,
                'message' => 'File deleted successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Delete presentation file failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get editable content data for a presentation file
     */
    public function getPresentationFileContent($fileId, $userId)
    {
        $file = PresentationFile::where('id', $fileId)
            ->where('user_id', $userId)
            ->first();

        if (!$file) {
            return [
                'success' => false,
                'error' => 'File not found or access denied'
            ];
        }

        if ($file->isExpired()) {
            return [
                'success' => false,
                'error' => 'File has expired'
            ];
        }

        if (!$file->content_data) {
            return [
                'success' => false,
                'error' => 'Content data not available for this file. This file may have been created before the edit feature was added. Please re-export the presentation to enable editing.',
                'file_exists' => true,
                'can_re_export' => true
            ];
        }

        return [
            'success' => true,
            'content' => $file->content_data,
            'file_id' => $file->id,
            'title' => $file->title,
            'template' => $file->template,
            'color_scheme' => $file->color_scheme,
            'font_style' => $file->font_style
        ];
    }

    /**
     * Get presentation file for download (with authentication check)
     */
    public function getPresentationFileForDownload($fileId, $userId)
    {
        $file = PresentationFile::where('id', $fileId)
            ->where('user_id', $userId)
            ->first();

        if (!$file) {
            return [
                'success' => false,
                'error' => 'File not found or access denied'
            ];
        }

        if ($file->isExpired()) {
            return [
                'success' => false,
                'error' => 'File has expired'
            ];
        }

        return [
            'success' => true,
            'file' => $file,
            'file_path' => Storage::disk('public')->path($file->file_path)
        ];
    }

    /**
     * Get available templates from microservice
     */
    public function getAvailableTemplates()
    {
        try {
            // Try to fetch templates from microservice
            $headers = [
                'Accept' => 'application/json'
            ];
            
            if ($this->microserviceApiKey) {
                $headers['X-API-Key'] = $this->microserviceApiKey;
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->get($this->microserviceUrl . '/templates');

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Microservice templates response', [
                    'status' => $response->status(),
                    'response_keys' => is_array($responseData) ? array_keys($responseData) : [],
                    'response_structure' => $responseData
                ]);
                
                $templates = [];
                
                // Handle different response formats
                // Format 1: { "success": true, "templates": [...] }
                if (isset($responseData['success']) && isset($responseData['templates']) && is_array($responseData['templates'])) {
                    foreach ($responseData['templates'] as $template) {
                        if (isset($template['id'])) {
                            $templates[$template['id']] = [
                                'name' => $template['name'] ?? $template['id'],
                                'description' => $template['description'] ?? 'Presentation template',
                                'color_scheme' => $this->extractColorScheme($template['colors'] ?? []),
                                'category' => $this->inferCategory($template['id']),
                                'colors' => $template['colors'] ?? [],
                                'preview' => $template['preview'] ?? null
                            ];
                        }
                    }
                }
                // Format 2: Direct array of templates
                elseif (is_array($responseData) && isset($responseData[0]) && is_array($responseData[0])) {
                    foreach ($responseData as $template) {
                        $id = $template['id'] ?? $template['template_id'] ?? $template['name'] ?? null;
                        if ($id) {
                            $templates[$id] = [
                                'name' => $template['name'] ?? $id,
                                'description' => $template['description'] ?? 'Presentation template',
                                'color_scheme' => $this->extractColorScheme($template['colors'] ?? $template['color_scheme'] ?? []),
                                'category' => $this->inferCategory($id),
                                'colors' => $template['colors'] ?? [],
                                'preview' => $template['preview'] ?? null
                            ];
                        }
                    }
                }
                // Format 3: Object with template IDs as keys
                elseif (is_array($responseData) && !isset($responseData[0])) {
                    foreach ($responseData as $id => $template) {
                        if (is_array($template)) {
                            $templates[$id] = [
                                'name' => $template['name'] ?? $id,
                                'description' => $template['description'] ?? 'Presentation template',
                                'color_scheme' => $this->extractColorScheme($template['colors'] ?? $template['color_scheme'] ?? []),
                                'category' => $this->inferCategory($id),
                                'colors' => $template['colors'] ?? [],
                                'preview' => $template['preview'] ?? null
                            ];
                        }
                    }
                }
                
                // Only return microservice templates if we got at least one
                if (!empty($templates)) {
                    Log::info('Successfully fetched templates from microservice', [
                        'count' => count($templates),
                        'template_ids' => array_keys($templates)
                    ]);
                    return $templates;
                }
            }

            // Fallback to hardcoded templates if microservice is unavailable
            Log::warning('Failed to fetch templates from microservice, using fallback', [
                'http_code' => $response->status() ?? 'unknown',
                'response_body' => $response->body() ?? 'no body'
            ]);

        } catch (\Exception $e) {
            Log::warning('Error fetching templates from microservice, using fallback', [
                'error' => $e->getMessage(),
                'url' => $this->microserviceUrl . '/templates'
            ]);
        }

        // Always return fallback templates
        return [
            'corporate_blue' => [
                'name' => 'Corporate Blue',
                'description' => 'Professional blue theme for business presentations',
                'color_scheme' => 'blue',
                'category' => 'business'
            ],
            'modern_white' => [
                'name' => 'Modern White',
                'description' => 'Clean white theme with modern typography',
                'color_scheme' => 'white',
                'category' => 'modern'
            ],
            'creative_colorful' => [
                'name' => 'Creative Colorful',
                'description' => 'Vibrant colors for creative presentations',
                'color_scheme' => 'colorful',
                'category' => 'creative'
            ],
            'minimalist_gray' => [
                'name' => 'Minimalist Gray',
                'description' => 'Simple gray theme for focused content',
                'color_scheme' => 'gray',
                'category' => 'minimalist'
            ]
        ];
    }

    /**
     * Extract color scheme from color array
     */
    private function extractColorScheme($colors)
    {
        if (empty($colors)) {
            return 'blue';
        }

        // Try to infer color scheme from first color
        $firstColor = strtolower($colors[0] ?? '');
        
        if (strpos($firstColor, 'blue') !== false || strpos($firstColor, '003366') !== false || strpos($firstColor, '0066cc') !== false) {
            return 'blue';
        } elseif (strpos($firstColor, 'white') !== false || strpos($firstColor, 'ffffff') !== false) {
            return 'white';
        } elseif (strpos($firstColor, 'ff6b6b') !== false || strpos($firstColor, '4ecdc4') !== false) {
            return 'colorful';
        } elseif (strpos($firstColor, '2c3e50') !== false || strpos($firstColor, 'gray') !== false || strpos($firstColor, 'grey') !== false) {
            return 'gray';
        }

        return 'blue'; // Default
    }

    /**
     * Infer category from template ID
     */
    private function inferCategory($templateId)
    {
        $id = strtolower($templateId);
        
        if (strpos($id, 'corporate') !== false || strpos($id, 'business') !== false) {
            return 'business';
        } elseif (strpos($id, 'modern') !== false) {
            return 'modern';
        } elseif (strpos($id, 'creative') !== false || strpos($id, 'colorful') !== false) {
            return 'creative';
        } elseif (strpos($id, 'minimalist') !== false) {
            return 'minimalist';
        }

        return 'business'; // Default
    }

    /**
     * Save presentation data (JSON) directly to database
     */
    public function savePresentationData($aiResultId, $presentationData, $userId)
    {
        try {
            Log::info('Saving presentation data to database', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'data_keys' => array_keys($presentationData)
            ]);

            // Find the AI result - use flexible lookup for public access
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Update the AI result with the new presentation data
            $aiResult->update([
                'result_data' => $presentationData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'saved_at' => now()->toISOString(),
                    'saved_by' => 'user_edit',
                    'version' => ($aiResult->metadata['version'] ?? 0) + 1,
                    'last_edited_by' => $userId
                ])
            ]);

            Log::info('Presentation data saved successfully', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'version' => $aiResult->metadata['version'] ?? 1
            ]);

            return [
                'success' => true,
                'data' => [
                    'ai_result_id' => $aiResultId,
                    'updated_at' => $aiResult->updated_at->toISOString(),
                    'version' => $aiResult->metadata['version'] ?? 1
                ],
                'message' => 'Presentation saved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Save presentation data failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Save failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Call FastAPI microservice
     * 
     * @param string $url The microservice endpoint URL
     * @param array $data Request data
     * @param bool $async If true, return job_id immediately. If false, poll until completion (default: false for backward compatibility)
     * @return array Response data
     */
    private function callMicroservice($url, $data, $async = false)
    {
        try {
            // Prepare headers with API key
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];
            
            if ($this->microserviceApiKey) {
                $headers['X-API-Key'] = $this->microserviceApiKey;
            }

            // Make HTTP request using Laravel Http facade
            // Ensure data is properly serializable
            try {
                $response = Http::timeout($this->microserviceTimeout)
                    ->withHeaders($headers)
                    ->post($url, $data);
            } catch (\TypeError $e) {
                // Handle type errors (array to string conversion)
                Log::error('HTTP request type error', [
                    'error' => $e->getMessage(),
                    'url' => $url,
                    'data_type' => gettype($data),
                    'trace' => $e->getTraceAsString()
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid data format for microservice request: ' . $e->getMessage()
                ];
            } catch (\Exception $e) {
                // Handle other exceptions
                Log::error('HTTP request exception', [
                    'error' => $e->getMessage(),
                    'url' => $url,
                    'trace' => $e->getTraceAsString()
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to send request to microservice: ' . $e->getMessage()
                ];
            }

            // Check for connection errors
            if ($response->failed() && $response->status() === 0) {
                Log::error('Microservice connection failed', [
                    'url' => $url,
                    'error' => 'Could not connect to microservice'
                ]);
                return [
                    'success' => false,
                    'error' => 'Could not connect to presentation microservice. Please ensure the service is running.'
                ];
            }

            if (!$response->successful()) {
                $errorBody = $response->body();
                $statusCode = $response->status();
                $errorMessage = 'Microservice request failed: ' . (string)$statusCode;
                
                // Try to extract error message from response body
                if ($errorBody) {
                    $errorData = json_decode($errorBody, true);
                    
                    // Log the raw error for debugging
                    Log::info('Microservice error response', [
                        'http_code' => $response->status(),
                        'raw_body' => $errorBody,
                        'parsed_error' => $errorData
                    ]);
                    
                    if (is_array($errorData)) {
                        if (isset($errorData['error']['message']) && is_string($errorData['error']['message'])) {
                            $errorMessage = $errorData['error']['message'];
                        } elseif (isset($errorData['detail']) && is_string($errorData['detail'])) {
                            $errorMessage = $errorData['detail'];
                        } elseif (isset($errorData['error'])) {
                            if (is_string($errorData['error'])) {
                                $errorMessage = $errorData['error'];
                            } elseif (is_array($errorData['error']) && isset($errorData['error']['message']) && is_string($errorData['error']['message'])) {
                                $errorMessage = $errorData['error']['message'];
                            } else {
                                // If error is an array or other non-string type, convert to JSON
                                $errorMessage = 'Microservice error: ' . json_encode($errorData['error'], JSON_UNESCAPED_UNICODE);
                            }
                        } elseif (isset($errorData['message']) && is_string($errorData['message'])) {
                            $errorMessage = $errorData['message'];
                        } else {
                            // If we can't extract a string error, use JSON representation
                            $errorMessage = 'Microservice error: ' . json_encode($errorData, JSON_UNESCAPED_UNICODE);
                        }
                    }
                }
                
                // Ensure error message is always a string
                $errorMessage = is_string($errorMessage) ? $errorMessage : json_encode($errorMessage, JSON_UNESCAPED_UNICODE);
                
                Log::error('Microservice HTTP error', [
                    'http_code' => $statusCode,
                    'error' => $errorMessage,
                    'url' => $url,
                    'error_body' => is_string($errorBody) ? substr($errorBody, 0, 500) : json_encode($errorBody)
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage
                ];
            }

            $responseData = $response->json();

            // Validate response data is an array
            if (!is_array($responseData)) {
                Log::error('Microservice returned invalid JSON response', [
                    'response_body' => $response->body(),
                    'url' => $url
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from microservice'
                ];
            }

            // Check if this is an async job response (new API v3.1.0 format)
            if (isset($responseData['job_id']) && isset($responseData['status'])) {
                if ($async) {
                    // Return job_id immediately for async handling
                    return [
                        'success' => true,
                        'job_id' => $responseData['job_id'],
                        'status' => $responseData['status'],
                        'message' => $responseData['message'] ?? 'Job submitted successfully'
                    ];
                } else {
                    // Poll for job completion (backward compatibility)
                    return $this->pollJobResult($responseData['job_id']);
                }
            }

            // Handle direct response (legacy format, shouldn't happen with new API)
            if (isset($responseData['success'])) {
                return $responseData;
            }

            // Wrap response in success format if needed
            return [
                'success' => true,
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Microservice call exception', [
                'error' => $e->getMessage(),
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'Microservice call failed: ' . $e->getMessage()
            ];
        } catch (\Throwable $e) {
            Log::error('Microservice call fatal error', [
                'error' => $e->getMessage(),
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'Microservice call failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Poll job result for async processing
     */
    private function pollJobResult($jobId, $maxAttempts = 300, $intervalSeconds = 2)
    {
        $headers = [
            'Accept' => 'application/json'
        ];
        
        if ($this->microserviceApiKey) {
            $headers['X-API-Key'] = $this->microserviceApiKey;
        }

        Log::info('Polling presentation job', [
            'job_id' => $jobId,
            'max_attempts' => $maxAttempts,
            'interval' => $intervalSeconds
        ]);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // Check job status
            $statusResponse = Http::timeout(10)
                ->withHeaders($headers)
                ->get($this->microserviceUrl . '/jobs/' . $jobId . '/status');

            if (!$statusResponse->successful()) {
                Log::error('Failed to check job status', [
                    'job_id' => $jobId,
                    'http_code' => $statusResponse->status()
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to check job status: ' . $statusResponse->status()
                ];
            }

            $statusData = $statusResponse->json();
            
            // Validate status data is an array
            if (!is_array($statusData)) {
                Log::error('Invalid job status response', [
                    'job_id' => $jobId,
                    'response_body' => $statusResponse->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid job status response from microservice'
                ];
            }
            
            // Handle new API v3.1.0 response structure
            if (isset($statusData['success']) && isset($statusData['data']) && is_array($statusData['data'])) {
                $status = $statusData['data']['status'] ?? 'unknown';
                $progress = $statusData['data']['progress'] ?? null;
            } else {
                // Legacy format support
                $status = $statusData['status'] ?? 'unknown';
                $progress = $statusData['progress'] ?? null;
            }

            Log::info('Job status check', [
                'job_id' => $jobId,
                'status' => $status,
                'progress' => $progress,
                'attempt' => $attempt + 1
            ]);

            if ($status === 'completed') {
                // Get result
                $resultResponse = Http::timeout(30)
                    ->withHeaders($headers)
                    ->get($this->microserviceUrl . '/jobs/' . $jobId . '/result');

                if (!$resultResponse->successful()) {
            return [
                'success' => false,
                        'error' => 'Failed to get job result: ' . $resultResponse->status()
                    ];
                }

                $resultData = $resultResponse->json();
                
                // Handle new API v3.1.0 response structure
                if (isset($resultData['success']) && isset($resultData['data'])) {
                    // Check if data is nested (data.data) or direct (data)
                    if (isset($resultData['data']['data'])) {
                        $data = $resultData['data']['data'];
                        $metadata = $resultData['data']['metadata'] ?? [];
                    } else {
                        $data = $resultData['data'];
                        $metadata = $resultData['metadata'] ?? [];
                    }
                } else {
                    // Legacy format support
                    $data = $resultData['data'] ?? $resultData;
                    $metadata = $resultData['metadata'] ?? [];
                }
                
                return [
                    'success' => true,
                    'data' => $data,
                    'metadata' => $metadata
                ];
            } elseif ($status === 'failed') {
                // Handle new API v3.1.0 error structure
                if (isset($statusData['data']['error'])) {
                    $error = is_string($statusData['data']['error']) 
                        ? $statusData['data']['error'] 
                        : ($statusData['data']['error']['message'] ?? 'Job processing failed');
                } else {
                    $error = $statusData['error'] ?? 'Job processing failed';
                }
                
                Log::error('Job failed', [
                    'job_id' => $jobId,
                    'error' => $error
                ]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            } elseif ($status === 'cancelled') {
                return [
                    'success' => false,
                    'error' => 'Job was cancelled'
                ];
            }

            // Wait before next attempt
            sleep($intervalSeconds);
        }

        Log::error('Job polling timeout', [
            'job_id' => $jobId,
            'max_attempts' => $maxAttempts
        ]);

        return [
            'success' => false,
            'error' => 'Job processing timeout after ' . ($maxAttempts * $intervalSeconds) . ' seconds'
        ];
    }

    /**
     * Export presentation to PowerPoint (on-demand)
     */
    public function exportPresentationToPowerPoint($aiResultId, $presentationData, $userId, $templateData = null)
    {
        try {
            Log::info('Exporting presentation to PowerPoint using FastAPI microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            // Get the AI result to access the outline data - use flexible lookup
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Always use the most up-to-date content from the database
            // This ensures we use the generated content instead of just the outline
            $slides = $aiResult->result_data['slides'] ?? [];
            
            // If frontend sent updated slides, merge them with the database content
            if (isset($presentationData['slides']) && is_array($presentationData['slides'])) {
                // Merge frontend updates with database content, prioritizing database content for full content
                foreach ($presentationData['slides'] as $frontendSlide) {
                    $slideIndex = $frontendSlide['slide_number'] ?? null;
                    if ($slideIndex !== null) {
                        // Find matching slide in database
                        foreach ($slides as &$dbSlide) {
                            if (($dbSlide['slide_number'] ?? 0) == $slideIndex) {
                                // Preserve the generated content from database first
                                $preservedContent = $dbSlide['content'] ?? null;
                                
                                // Update with frontend changes but preserve generated content
                                $dbSlide = array_merge($dbSlide, $frontendSlide);
                                
                                // Always prioritize database content over frontend content
                                if ($preservedContent && (!isset($frontendSlide['content']) || empty($frontendSlide['content']))) {
                                    $dbSlide['content'] = $preservedContent;
                                }
                                break;
                            }
                        }
                    }
                }
            }

            // Prepare data for FastAPI microservice (API v3.1.0)
            $requestData = [
                'presentation_data' => [
                    'title' => $aiResult->result_data['title'] ?? 'Presentation',
                    'slides' => $slides
                ],
                'user_id' => $userId,
                'ai_result_id' => $aiResultId,
                'template' => $templateData['template'] ?? 'corporate_blue',
                'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                'font_style' => $templateData['font_style'] ?? 'modern',
                'generate_missing_content' => true // API v3.1.0 parameter
            ];
            
            // Log the data being sent for debugging
            Log::info('Data being sent to microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'slides_count' => count($slides),
                'first_slide_content' => $slides[0]['content'] ?? 'No content',
                'first_slide_subheaders' => $slides[0]['subheaders'] ?? 'No subheaders',
                'has_generated_content' => isset($slides[0]['content']) && !empty($slides[0]['content']),
                'content_length' => isset($slides[0]['content']) ? strlen($slides[0]['content']) : 0,
                'frontend_data_received' => isset($presentationData['slides']) ? count($presentationData['slides']) : 0
            ]);

            // Call enhanced microservice with content generation capability
            $response = $this->callMicroservice($this->microserviceUrl . '/export', $requestData);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Microservice call failed'
                ];
            }

            // Handle new response format with base64 file content
            $filename = $response['data']['filename'] ?? 'presentation_' . $aiResultId . '.pptx';
            $fileContent = base64_decode($response['data']['file_content']);
            $fileSize = $response['data']['file_size'] ?? strlen($fileContent);
            
            // Save PowerPoint file to Laravel storage
            $storagePath = 'presentations/' . $filename;
            Storage::disk('public')->put($storagePath, $fileContent);
            
            // Generate download URL
            $downloadUrl = Storage::disk('public')->url($storagePath);

            // Update AI result with export info
            $resultData = $aiResult->result_data;
            $resultData['powerpoint_file'] = $storagePath;
            $resultData['powerpoint_filename'] = $filename;
            $resultData['powerpoint_size'] = $fileSize;
            $resultData['exported_at'] = now()->toISOString();

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'exported_at' => now()->toISOString(),
                    'exported_by' => 'fastapi_microservice',
                    'export_version' => ($aiResult->metadata['export_version'] ?? 0) + 1,
                    'file_size' => $fileSize,
                    'slides_count' => $response['metadata']['slides_count'] ?? count($resultData['slides'] ?? [])
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'powerpoint_file' => $storagePath,
                    'powerpoint_filename' => $filename,
                    'download_url' => $downloadUrl,
                    'file_size' => $fileSize,
                    'slides_count' => $response['metadata']['slides_count'] ?? count($resultData['slides'] ?? []),
                    'ai_result_id' => $aiResultId
                ],
                'message' => 'Presentation exported successfully using FastAPI microservice'
            ];

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get presentation data for frontend editing
     */
    public function getPresentationData($aiResultId, $userId)
    {
        try {
            // Use flexible lookup for public access
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Return the presentation data for frontend editing
            return [
                'success' => true,
                'data' => $aiResult->result_data,
                'metadata' => $aiResult->metadata
            ];

        } catch (\Exception $e) {
            Log::error('Get presentation data failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get presentation data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate PowerPoint using FastAPI microservice
     */
    public function generatePowerPointWithMicroservice($aiResultId, $templateData, $userId)
    {
        try {
            Log::info('Starting PowerPoint generation with microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'template' => $templateData['template'] ?? 'default'
            ]);

            // Use flexible lookup for public access
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            // If not found and we have a specific user_id, try with user_id filter
            if (!$aiResult && $userId) {
                $aiResult = AIResult::where('id', $aiResultId)
                    ->where('user_id', $userId)
                    ->where('tool_type', 'presentation')
                    ->first();
            }

            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Validate result data
            if (!$aiResult->result_data || !is_array($aiResult->result_data)) {
                return [
                    'success' => false,
                    'error' => 'Invalid presentation data. Please regenerate the outline.'
                ];
            }

            // Always use the most up-to-date content from the database
            $resultData = $aiResult->result_data;
            
            // Prepare data for FastAPI microservice (API v3.1.0)
            $requestData = [
                'presentation_data' => [
                    'title' => $resultData['title'] ?? 'Presentation',
                    'slides' => $resultData['slides'] ?? []
                ],
                'user_id' => $userId,
                'ai_result_id' => $aiResultId,
                'template' => $templateData['template'] ?? 'corporate_blue',
                'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                'font_style' => $templateData['font_style'] ?? 'modern',
                'generate_missing_content' => true // API v3.1.0 parameter
            ];
            
            Log::info('Data being sent to microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'request_data' => $requestData,
                'slides_count' => count($resultData['slides'] ?? []),
                'has_generated_content' => isset($resultData['slides'][0]['content']) && !empty($resultData['slides'][0]['content'])
            ]);

            // Call FastAPI microservice
            $response = $this->callMicroservice($this->microserviceUrl . '/export', $requestData);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Microservice call failed'
                ];
            }

            // Handle new response format with base64 file content
            $filename = $response['data']['filename'] ?? 'presentation_' . $aiResultId . '.pptx';
            $fileContent = base64_decode($response['data']['file_content']);
            $fileSize = $response['data']['file_size'] ?? strlen($fileContent);
            
            // Save PowerPoint file to Laravel storage
            $storagePath = 'presentations/' . $filename;
            Storage::disk('public')->put($storagePath, $fileContent);
            
            // Generate download URL
            $downloadUrl = Storage::disk('public')->url($storagePath);

            // Update AI result with PowerPoint file
            $resultData['powerpoint_file'] = $storagePath;
            $resultData['powerpoint_filename'] = $filename;
            $resultData['powerpoint_size'] = $fileSize;
            $resultData['step'] = 'powerpoint_generated';
            $resultData['template_used'] = $templateData['template'] ?? 'corporate_blue';

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'template' => $templateData['template'] ?? 'corporate_blue',
                    'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                    'font_style' => $templateData['font_style'] ?? 'modern',
                    'generated_at' => now()->toISOString(),
                    'generated_by' => 'fastapi_microservice',
                    'file_size' => $fileSize,
                    'slides_count' => $response['metadata']['slides_count'] ?? count($resultData['slides'] ?? [])
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'powerpoint_file' => $storagePath,
                    'powerpoint_filename' => $filename,
                    'download_url' => $downloadUrl,
                    'file_size' => $fileSize,
                    'slides_count' => $response['metadata']['slides_count'] ?? count($resultData['slides'] ?? []),
                    'ai_result_id' => $aiResultId
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Microservice PowerPoint generation failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate PowerPoint with microservice: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if microservice is available
     */
    public function isMicroserviceAvailable()
    {
        try {
            $response = Http::timeout(5)->get($this->microserviceUrl . '/health');
            $responseData = $response->json();
            return $response->successful() && ($responseData['status'] ?? '') === 'healthy';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get job status from microservice (API v3.1.0)
     * 
     * @param string $jobId Job ID
     * @return array Job status information
     */
    public function getJobStatus($jobId)
    {
        try {
            $headers = [
                'Accept' => 'application/json'
            ];
            
            if ($this->microserviceApiKey) {
                $headers['X-API-Key'] = $this->microserviceApiKey;
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->get($this->microserviceUrl . '/jobs/' . $jobId . '/status');

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get job status: ' . $response->status()
                ];
            }

            $responseData = $response->json();
            
            // Clear response from memory immediately after use
            unset($response);
            
            // Handle new API v3.1.0 response structure
            if (isset($responseData['success']) && isset($responseData['data'])) {
                $result = [
                    'success' => true,
                    'data' => $responseData['data']
                ];
                unset($responseData);
                return $result;
            }

            // Legacy format support
            $result = [
                'success' => true,
                'data' => $responseData
            ];
            unset($responseData);
            return $result;

        } catch (\Exception $e) {
            Log::error('Get job status failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get job status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get job result from microservice (API v3.1.0)
     * 
     * @param string $jobId Job ID
     * @return array Job result data
     */
    public function getJobResult($jobId)
    {
        try {
            $headers = [
                'Accept' => 'application/json'
            ];
            
            if ($this->microserviceApiKey) {
                $headers['X-API-Key'] = $this->microserviceApiKey;
            }

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->get($this->microserviceUrl . '/jobs/' . $jobId . '/result');

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);
                
                $errorMessage = 'Failed to get job result: ' . $response->status();
                if (isset($errorData['error']['message'])) {
                    $errorMessage = $errorData['error']['message'];
                } elseif (isset($errorData['error'])) {
                    $errorMessage = is_string($errorData['error']) 
                        ? $errorData['error'] 
                        : ($errorData['error']['message'] ?? $errorMessage);
                }

                return [
                    'success' => false,
                    'error' => $errorMessage
                ];
            }

            $responseData = $response->json();
            
            // Clear response from memory immediately after use
            unset($response);
            
            // Handle new API v3.1.0 response structure
            if (isset($responseData['success'])) {
                if ($responseData['success']) {
                    // Extract nested data structure
                    $data = $responseData['data']['data'] ?? $responseData['data'] ?? null;
                    $metadata = $responseData['data']['metadata'] ?? $responseData['metadata'] ?? [];
                    
                    $result = [
                        'success' => true,
                        'data' => $data,
                        'metadata' => $metadata
                    ];
                    unset($responseData);
                    return $result;
                } else {
                    // Error response
                    $error = $responseData['error']['message'] ?? $responseData['error'] ?? 'Unknown error';
                    unset($responseData);
                    return [
                        'success' => false,
                        'error' => $error
                    ];
                }
            }

            // Legacy format support
            $result = [
                'success' => true,
                'data' => $responseData['data'] ?? $responseData
            ];
            unset($responseData);
            return $result;

        } catch (\Exception $e) {
            Log::error('Get job result failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get job result: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel a job (API v3.1.0)
     * 
     * @param string $jobId Job ID
     * @return array Cancellation result
     */
    public function cancelJob($jobId)
    {
        try {
            $headers = [
                'Accept' => 'application/json'
            ];
            
            if ($this->microserviceApiKey) {
                $headers['X-API-Key'] = $this->microserviceApiKey;
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($this->microserviceUrl . '/jobs/' . $jobId . '/cancel');

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to cancel job: ' . $response->status()
                ];
            }

            $responseData = $response->json();
            
            // Handle new API v3.1.0 response structure
            if (isset($responseData['success']) && isset($responseData['data'])) {
                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            }

            return [
                'success' => true,
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Cancel job failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to cancel job: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate outline structure
     */
    private function validateOutlineStructure($outline)
    {
        if (!is_array($outline)) {
            return ['valid' => false, 'error' => 'Outline must be an array'];
        }

        if (!isset($outline['title']) || empty($outline['title'])) {
            return ['valid' => false, 'error' => 'Outline must have a title'];
        }

        if (!isset($outline['slides']) || !is_array($outline['slides'])) {
            return ['valid' => false, 'error' => 'Outline must have a slides array'];
        }

        if (empty($outline['slides'])) {
            return ['valid' => false, 'error' => 'Outline must have at least one slide'];
        }

        foreach ($outline['slides'] as $index => $slide) {
            if (!is_array($slide)) {
                return ['valid' => false, 'error' => "Slide at index {$index} must be an array"];
            }

            if (!isset($slide['header']) || empty($slide['header'])) {
                return ['valid' => false, 'error' => "Slide at index {$index} must have a header"];
            }

            if (!isset($slide['subheaders']) || !is_array($slide['subheaders'])) {
                return ['valid' => false, 'error' => "Slide at index {$index} must have a subheaders array"];
            }
        }

        return ['valid' => true];
    }

    /**
     * Transform content for export (convert string content to array)
     * Ensures compatibility with microservice expectations
     */
    private function transformContentForExport($data)
    {
        if (!is_array($data) || !isset($data['slides']) || !is_array($data['slides'])) {
            return $data;
        }

        // Transform each slide's content field from string to array if needed
        foreach ($data['slides'] as &$slide) {
            if (!isset($slide['content'])) {
                // If content is missing, create empty array
                $slide['content'] = [];
                continue;
            }

            // If content is already an array, keep it as-is
            if (is_array($slide['content'])) {
                continue;
            }

            // If content is a string, convert to array of bullet points
            if (is_string($slide['content'])) {
                $slide['content'] = $this->convertStringContentToArray($slide['content']);
            }
        }

        return $data;
    }

    /**
     * Convert string content to array of bullet points
     * Handles various formats: newlines, bullet markers, paragraphs
     */
    private function convertStringContentToArray($content)
    {
        if (empty($content)) {
            return [];
        }

        $lines = [];
        
        // Split by double newlines (paragraphs)
        $paragraphs = preg_split('/\n\s*\n/', $content);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                continue;
            }

            // Check if paragraph contains bullet markers
            if (preg_match('/^[•\-\*]\s+/', $paragraph) || preg_match('/^\d+\.\s+/', $paragraph)) {
                // Already has bullet markers, split by newlines
                $bulletLines = preg_split('/\n/', $paragraph);
                foreach ($bulletLines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        // Remove bullet markers if present
                        $line = preg_replace('/^[•\-\*]\s+/', '', $line);
                        $line = preg_replace('/^\d+\.\s+/', '', $line);
                        $lines[] = $line;
                    }
                }
            } else {
                // No bullet markers, try to split by single newlines
                $singleLines = preg_split('/\n/', $paragraph);
                if (count($singleLines) > 1) {
                    // Multiple lines, treat each as a bullet point
                    foreach ($singleLines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $lines[] = $line;
                        }
                    }
                } else {
                    // Single paragraph, try to split by sentences if too long
                    $paragraph = trim($paragraph);
                    if (strlen($paragraph) > 200) {
                        // Long paragraph, split by sentences
                        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph);
                        foreach ($sentences as $sentence) {
                            $sentence = trim($sentence);
                            if (!empty($sentence)) {
                                $lines[] = $sentence;
                            }
                        }
                    } else {
                        // Short paragraph, use as single bullet point
                        $lines[] = $paragraph;
                    }
                }
            }
        }

        // If no lines were created, use the original content as a single item
        if (empty($lines)) {
            $lines[] = $content;
        }

        return $lines;
    }

    /**
     * Validate content structure (for export)
     */
    private function validateContentStructure($content)
    {
        if (!is_array($content)) {
            return ['valid' => false, 'error' => 'Content must be an array'];
        }

        if (!isset($content['title']) || empty($content['title'])) {
            return ['valid' => false, 'error' => 'Content must have a title'];
        }

        if (!isset($content['slides']) || !is_array($content['slides'])) {
            return ['valid' => false, 'error' => 'Content must have a slides array'];
        }

        if (empty($content['slides'])) {
            return ['valid' => false, 'error' => 'Content must have at least one slide'];
        }

        foreach ($content['slides'] as $index => $slide) {
            if (!is_array($slide)) {
                return ['valid' => false, 'error' => "Slide at index {$index} must be an array"];
            }

            if (!isset($slide['header']) || empty($slide['header'])) {
                return ['valid' => false, 'error' => "Slide at index {$index} must have a header"];
            }

            // Content field is optional and can be string or array
            // Validation will pass regardless of content format
            // Transformation happens in controller before validation
        }

        return ['valid' => true];
    }

    /**
     * Call microservice (public wrapper for external use)
     * 
     * @param string $url The microservice endpoint URL
     * @param array $data Request data
     * @return array Response data
     */
    public function callMicroservicePublic($url, $data)
    {
        return $this->callMicroservice($url, $data, false);
    }
}

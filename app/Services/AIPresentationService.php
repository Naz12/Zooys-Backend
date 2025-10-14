<?php

namespace App\Services;

use App\Models\AIResult;
use App\Services\OpenAIService;
use App\Services\AIResultService;
use App\Services\Modules\ContentExtractionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AIPresentationService
{
    private $openAIService;
    private $aiResultService;
    private $contentExtractionService;
    private $microserviceUrl;

    public function __construct(
        OpenAIService $openAIService,
        AIResultService $aiResultService,
        ContentExtractionService $contentExtractionService
    ) {
        $this->openAIService = $openAIService;
        $this->aiResultService = $aiResultService;
        $this->contentExtractionService = $contentExtractionService;
        $this->microserviceUrl = config('services.powerpoint_microservice.url', 'http://localhost:8001');
    }

    /**
     * Generate presentation outline from user input
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

            // Generate presentation outline using microservice
            $outline = $this->callMicroserviceForOutline($content['content'], $inputData);

            if (!$outline['success']) {
                return [
                    'success' => false,
                    'error' => $outline['error']
                ];
            }

            // Save initial result
            $aiResult = $this->aiResultService->saveResult(
                $userId,
                'presentation',
                $outline['data']['title'],
                'AI-generated presentation outline',
                $inputData,
                $outline['data'],
                [
                    'step' => 'outline_generated',
                    'input_type' => $inputData['input_type'] ?? 'text',
                    'language' => $inputData['language'] ?? 'English',
                    'tone' => $inputData['tone'] ?? 'Professional',
                    'length' => $inputData['length'] ?? 'Medium',
                    'model' => $inputData['model'] ?? 'Basic Model'
                ]
            );

            if (!$aiResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to save presentation outline: ' . $aiResult['error']
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'outline' => $outline['data'],
                    'ai_result_id' => $aiResult['ai_result']->id
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Presentation outline generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'input_data' => $inputData
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate presentation outline: ' . $e->getMessage()
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

        $response = $this->openAIService->generateResponse($prompt, 'gpt-4');

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

        $response = $this->openAIService->generateResponse($prompt, 'gpt-4');

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

            // Prepare request data for microservice
            $requestData = [
                'content' => $content,
                'language' => $language,
                'tone' => $tone,
                'length' => $length,
                'model' => $model
            ];

            Log::info('Calling microservice for outline generation', [
                'url' => $this->microserviceUrl . '/generate-outline',
                'request_data' => $requestData,
                'content_length' => strlen($content)
            ]);

            // Call microservice
            $response = $this->callMicroservice($this->microserviceUrl . '/generate-outline', $requestData);

            Log::info('Microservice response received', [
                'success' => $response['success'] ?? false,
                'response_keys' => array_keys($response),
                'error' => $response['error'] ?? null
            ]);

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
            // Prepare request data for microservice
            $requestData = [
                'outline' => $outline,
                'language' => $language,
                'tone' => $tone,
                'detail_level' => $detailLevel
            ];

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
     */
    public function generateFullContent($aiResultId, $userId, $language = 'English', $tone = 'Professional', $detailLevel = 'detailed')
    {
        try {
            Log::info('Starting full content generation', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            // Get the AI result
            $aiResult = AIResult::where('id', $aiResultId)
                ->where('tool_type', 'presentation')
                ->first();
            
            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Presentation not found'
                ];
            }

            // Get outline from result data
            $outline = $aiResult->result_data;
            if (!$outline || !isset($outline['slides'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid outline data'
                ];
            }

            // Generate content using microservice
            $contentResult = $this->callMicroserviceForContent($outline, $language, $tone, $detailLevel);

            if (!$contentResult['success']) {
                return [
                    'success' => false,
                    'error' => $contentResult['error']
                ];
            }

            // Update AI result with full content
            $aiResult->update([
                'result_data' => $contentResult['data'],
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'content_generated_at' => now()->toISOString(),
                    'content_generated_by' => 'microservice',
                    'language' => $language,
                    'tone' => $tone,
                    'detail_level' => $detailLevel
                ])
            ]);

            return [
                'success' => true,
                'data' => $contentResult['data'],
                'message' => 'Full content generated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Full content generation failed', [
                'error' => $e->getMessage(),
                'ai_result_id' => $aiResultId,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate full content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available templates
     */
    public function getAvailableTemplates()
    {
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
            ],
            'academic_formal' => [
                'name' => 'Academic Formal',
                'description' => 'Formal theme for educational presentations',
                'color_scheme' => 'dark',
                'category' => 'academic'
            ],
            'tech_modern' => [
                'name' => 'Tech Modern',
                'description' => 'Modern tech theme with teal and green accents',
                'color_scheme' => 'teal',
                'category' => 'tech'
            ],
            'elegant_purple' => [
                'name' => 'Elegant Purple',
                'description' => 'Sophisticated purple theme for elegant presentations',
                'color_scheme' => 'purple',
                'category' => 'elegant'
            ],
            'professional_green' => [
                'name' => 'Professional Green',
                'description' => 'Professional green theme for corporate presentations',
                'color_scheme' => 'green',
                'category' => 'professional'
            ]
        ];
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
     */
    private function callMicroservice($url, $data)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('Microservice cURL error', ['error' => $error, 'url' => $url]);
                return [
                    'success' => false,
                    'error' => 'cURL error: ' . $error
                ];
            }

            if ($httpCode !== 200) {
                Log::error('Microservice HTTP error', [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'url' => $url
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP error ' . $httpCode . ': ' . $response
                ];
            }

            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Microservice JSON decode error', [
                    'json_error' => json_last_error_msg(),
                    'response' => $response
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from microservice'
                ];
            }

            return $decodedResponse;

        } catch (\Exception $e) {
            Log::error('Microservice call exception', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            return [
                'success' => false,
                'error' => 'Microservice call failed: ' . $e->getMessage()
            ];
        }
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
                                // Update with frontend changes but preserve generated content
                                $dbSlide = array_merge($dbSlide, $frontendSlide);
                                // If frontend slide has content, use it; otherwise keep database content
                                if (!isset($frontendSlide['content']) && isset($dbSlide['content'])) {
                                    // Keep the generated content from database
                                }
                                break;
                            }
                        }
                    }
                }
            }

            // Prepare data for FastAPI microservice
            $requestData = [
                'presentation_data' => [
                    'title' => $aiResult->result_data['title'] ?? 'Presentation',
                    'slides' => $slides
                ],
                'user_id' => $userId,
                'ai_result_id' => $aiResultId,
                'template' => $templateData['template'] ?? 'corporate_blue',
                'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                'font_style' => $templateData['font_style'] ?? 'modern'
            ];
            
            // Log the data being sent for debugging
            Log::info('Data being sent to microservice', [
                'ai_result_id' => $aiResultId,
                'user_id' => $userId,
                'request_data' => $requestData,
                'slides_count' => count($slides),
                'first_slide_content' => $slides[0]['content'] ?? 'No content',
                'first_slide_subheaders' => $slides[0]['subheaders'] ?? 'No subheaders',
                'has_generated_content' => isset($slides[0]['content']) && !empty($slides[0]['content'])
            ]);

            // Call enhanced microservice with content generation capability
            $microserviceUrl = config('services.presentation_microservice.url', 'http://localhost:8001');
            $response = $this->callMicroservice($microserviceUrl . '/export', $requestData);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Microservice call failed'
                ];
            }

            // Update AI result with export info
            $resultData = $aiResult->result_data;
            $resultData['powerpoint_file'] = $response['data']['file_path'];
            $resultData['exported_at'] = now()->toISOString();

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'exported_at' => now()->toISOString(),
                    'exported_by' => 'fastapi_microservice',
                    'export_version' => ($aiResult->metadata['export_version'] ?? 0) + 1
                ])
            ]);

            return [
                'success' => true,
                'data' => $response['data'],
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
            
            // Prepare data for FastAPI microservice
            $requestData = [
                'presentation_data' => [
                    'title' => $resultData['title'] ?? 'Presentation',
                    'slides' => $resultData['slides'] ?? []
                ],
                'user_id' => $userId,
                'ai_result_id' => $aiResultId,
                'template' => $templateData['template'] ?? 'corporate_blue',
                'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                'font_style' => $templateData['font_style'] ?? 'modern'
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

            // Update AI result with PowerPoint file
            $resultData['powerpoint_file'] = $response['data']['file_path'];
            $resultData['step'] = 'powerpoint_generated';
            $resultData['template_used'] = $templateData['template'] ?? 'corporate_blue';

            $aiResult->update([
                'result_data' => $resultData,
                'metadata' => array_merge($aiResult->metadata ?? [], [
                    'template' => $templateData['template'] ?? 'corporate_blue',
                    'color_scheme' => $templateData['color_scheme'] ?? 'blue',
                    'font_style' => $templateData['font_style'] ?? 'modern',
                    'generated_at' => now()->toISOString(),
                    'generated_by' => 'fastapi_microservice'
                ])
            ]);

            return [
                'success' => true,
                'data' => [
                    'powerpoint_file' => $response['data']['file_path'],
                    'download_url' => $response['data']['download_url'],
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
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}

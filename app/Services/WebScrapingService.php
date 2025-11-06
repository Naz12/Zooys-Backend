<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class WebScrapingService
{
    /**
     * Extract content from web URL using Smartproxy endpoint
     */
    public function extractContent($url)
    {
        // Try Smartproxy endpoint first for web links
        $smartproxyResult = $this->extractWithSmartproxy($url);
        if ($smartproxyResult['success']) {
            return $smartproxyResult;
        }
        
        // Fallback to original method
        return $this->extractWebContent($url);
    }

    /**
     * Extract web content using BrightData endpoint
     */
    public function extractWithSmartproxy($url)
    {
        try {
            Log::info("Web Scraping BrightData API Request", [
                'url' => $url
            ]);

            $payload = [
                'input' => [
                    ['url' => $url]
                ]
            ];

            $queryParams = [
                'dataset_id' => 'gd_lk56epmy2i5g7lzu0k',
                'format' => 'bundle',
                'headings' => 1,
                'max_paragraph_sentences' => 7,
                'include_meta' => 1
            ];

            $response = Http::timeout(120)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Client-Key' => config('services.youtube_transcriber.client_key', 'dev-local'),
                ])
                ->post(config('services.youtube_transcriber.url', 'https://transcriber.akmicroservice.com') . '/brightdata/scrape?' . http_build_query($queryParams), $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Handle BrightData response format
                $content = $data['article_text'] ?? $data['subtitle_text'] ?? '';
                
                Log::info("Web Scraping BrightData API Response successful", [
                    'url' => $url,
                    'content_length' => strlen($content),
                    'has_meta' => isset($data['meta'])
                ]);

                return [
                    'success' => true,
                    'content' => $content,
                    'title' => $data['meta']['title'] ?? $this->extractTitleFromUrl($url),
                    'url' => $url,
                    'meta' => $data['meta'] ?? null,
                    'source' => 'brightdata'
                ];
            } else {
                Log::error("Web Scraping BrightData API Response failed", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'BrightData web scraping failed: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error("Web Scraping BrightData API Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'BrightData web scraping failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract title from URL (simple implementation)
     */
    private function extractTitleFromUrl($url)
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? 'Unknown';
        $path = $parsedUrl['path'] ?? '';
        
        // Extract a meaningful title from the path
        if ($path && $path !== '/') {
            $pathParts = explode('/', trim($path, '/'));
            $lastPart = end($pathParts);
            if ($lastPart) {
                return ucwords(str_replace(['-', '_'], ' ', $lastPart));
            }
        }
        
        return $host;
    }

    /**
     * Extract content from web URL
     */
    public function extractWebContent($url)
    {
        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception('Invalid URL format. Please provide a valid web address.');
            }

            // Check if URL is accessible
            if (!$this->isUrlAccessible($url)) {
                throw new \Exception('The website is not accessible. It may be down or blocking automated requests.');
            }

            // Fetch the webpage
            $response = $this->fetchWebpage($url);
            
            if (!$response) {
                throw new \Exception('Failed to fetch the webpage. The site may be blocking automated requests.');
            }

            // Parse HTML content
            $crawler = new Crawler($response);
            
            // Extract article content
            $content = $this->extractArticleContent($crawler);
            
            if (empty($content)) {
                throw new \Exception('No readable content found on this webpage. It may be a login page, image gallery, or have restricted access.');
            }

            // Extract metadata
            $metadata = $this->extractMetadata($crawler, $url);

            return [
                'content' => $content,
                'metadata' => $metadata,
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('Web scraping error for URL: ' . $url . ' - ' . $e->getMessage());
            
            return [
                'content' => null,
                'metadata' => null,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if URL is accessible
     */
    private function isUrlAccessible($url)
    {
        try {
            // Some sites block HEAD; fall back to lightweight GET
            $response = Http::timeout(10)->head($url);
            if ($response->successful()) {
                return true;
            }
        } catch (\Exception $e) {
            // ignore and try GET
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114 Safari/537.36'
                ])
                ->get($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch webpage content
     */
    private function fetchWebpage($url)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch webpage. HTTP Status: ' . $response->status());
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::error('HTTP request failed for URL: ' . $url . ' - ' . $e->getMessage());
            throw new \Exception('Unable to access the webpage. Please check if the URL is correct and accessible.');
        }
    }

    /**
     * Extract article content from HTML
     */
    private function extractArticleContent(Crawler $crawler)
    {
        // Remove script and style elements
        $crawler->filter('script, style, nav, header, footer, aside, .advertisement, .ads, .sidebar')->each(function (Crawler $node) {
            $node->getNode(0)->parentNode->removeChild($node->getNode(0));
        });

        // Try different selectors for article content
        $selectors = [
            'article',
            '.article',
            '.post',
            '.content',
            '.entry-content',
            '.post-content',
            '.article-content',
            'main',
            '.main-content',
            '[role="main"]',
            '.story',
            '.article-body',
            '.entry',
            '.post-body'
        ];

        foreach ($selectors as $selector) {
            try {
                $content = $crawler->filter($selector)->text();
                if (!empty(trim($content)) && strlen($content) > 100) {
                    return $this->cleanText($content);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback: extract from body
        try {
            $content = $crawler->filter('body')->text();
            return $this->cleanText($content);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract metadata from webpage
     */
    private function extractMetadata(Crawler $crawler, $url)
    {
        $metadata = [
            'url' => $url,
            'title' => '',
            'description' => '',
            'author' => '',
            'published_date' => '',
            'word_count' => 0
        ];

        try {
            // Extract title
            $title = $crawler->filter('title')->text();
            $metadata['title'] = trim($title);
        } catch (\Exception $e) {
            $metadata['title'] = 'Untitled';
        }

        try {
            // Extract description
            $description = $crawler->filter('meta[name="description"]')->attr('content');
            if (empty($description)) {
                $description = $crawler->filter('meta[property="og:description"]')->attr('content');
            }
            $metadata['description'] = trim($description);
        } catch (\Exception $e) {
            $metadata['description'] = '';
        }

        try {
            // Extract author
            $author = $crawler->filter('meta[name="author"]')->attr('content');
            if (empty($author)) {
                $author = $crawler->filter('meta[property="article:author"]')->attr('content');
            }
            if (empty($author)) {
                $author = $crawler->filter('.author, .byline, [rel="author"]')->text();
            }
            $metadata['author'] = trim($author);
        } catch (\Exception $e) {
            $metadata['author'] = '';
        }

        try {
            // Extract published date
            $date = $crawler->filter('meta[property="article:published_time"]')->attr('content');
            if (empty($date)) {
                $date = $crawler->filter('meta[name="date"]')->attr('content');
            }
            if (empty($date)) {
                $date = $crawler->filter('time[datetime]')->attr('datetime');
            }
            $metadata['published_date'] = trim($date);
        } catch (\Exception $e) {
            $metadata['published_date'] = '';
        }

        return $metadata;
    }

    /**
     * Clean extracted text
     */
    private function cleanText($text)
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove common unwanted patterns
        $text = preg_replace('/\b(Click here|Read more|Continue reading|See more|View more)\b/i', '', $text);
        
        // Trim and return
        return trim($text);
    }

    /**
     * Get user-friendly error messages
     */
    public function getErrorMessage($exception)
    {
        $message = $exception->getMessage();
        
        // Common error patterns and user-friendly messages
        if (strpos($message, 'timeout') !== false) {
            return 'The website is taking too long to respond. Please try again later or check if the website is working.';
        }
        
        if (strpos($message, '404') !== false) {
            return 'The webpage was not found. Please check if the URL is correct.';
        }
        
        if (strpos($message, '403') !== false) {
            return 'Access to this webpage is forbidden. The website may be blocking automated requests.';
        }
        
        if (strpos($message, '500') !== false) {
            return 'The website is experiencing server errors. Please try again later.';
        }
        
        if (strpos($message, 'SSL') !== false || strpos($message, 'certificate') !== false) {
            return 'There is a security certificate issue with this website. Please try a different URL.';
        }
        
        if (strpos($message, 'DNS') !== false) {
            return 'The website address could not be found. Please check if the URL is correct.';
        }
        
        // Return original message if no pattern matches
        return $message;
    }
}

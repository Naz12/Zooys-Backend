<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FileProcessingMetricsService
{
    /**
     * Record file processing metrics
     */
    public function recordMetrics($toolType, $fileType, $processingTime, $success, $metadata = [])
    {
        $timestamp = now();
        $date = $timestamp->format('Y-m-d');
        $hour = $timestamp->format('H');
        
        // Record daily metrics
        $this->incrementCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:total");
        if ($success) {
            $this->incrementCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:success");
        } else {
            $this->incrementCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:failed");
        }
        
        // Record hourly metrics
        $this->incrementCounter("metrics:hourly:{$date}:{$hour}:{$toolType}:{$fileType}:total");
        if ($success) {
            $this->incrementCounter("metrics:hourly:{$date}:{$hour}:{$toolType}:{$fileType}:success");
        } else {
            $this->incrementCounter("metrics:hourly:{$date}:{$hour}:{$toolType}:{$fileType}:failed");
        }
        
        // Record processing time
        $this->recordProcessingTime($toolType, $fileType, $processingTime);
        
        // Record additional metadata
        if (!empty($metadata)) {
            $this->recordMetadata($toolType, $fileType, $metadata);
        }
        
        Log::info("File processing metrics recorded", [
            'tool_type' => $toolType,
            'file_type' => $fileType,
            'processing_time' => $processingTime,
            'success' => $success,
            'metadata' => $metadata
        ]);
    }

    /**
     * Get processing statistics
     */
    public function getStatistics($toolType = null, $fileType = null, $period = 'daily')
    {
        $date = now()->format('Y-m-d');
        $stats = [];
        
        if ($period === 'daily') {
            $stats = $this->getDailyStats($date, $toolType, $fileType);
        } elseif ($period === 'hourly') {
            $stats = $this->getHourlyStats($date, $toolType, $fileType);
        } elseif ($period === 'weekly') {
            $stats = $this->getWeeklyStats($toolType, $fileType);
        }
        
        return $stats;
    }

    /**
     * Get daily statistics
     */
    private function getDailyStats($date, $toolType = null, $fileType = null)
    {
        $stats = [
            'date' => $date,
            'total_processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'success_rate' => 0.0,
            'average_processing_time' => 0.0,
            'by_tool_type' => [],
            'by_file_type' => [],
            'processing_times' => []
        ];
        
        if ($toolType && $fileType) {
            // Specific tool and file type
            $total = $this->getCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:total");
            $success = $this->getCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:success");
            $failed = $this->getCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:failed");
            
            $stats['total_processed'] = $total;
            $stats['successful'] = $success;
            $stats['failed'] = $failed;
            $stats['success_rate'] = $total > 0 ? ($success / $total) * 100 : 0;
            $stats['average_processing_time'] = $this->getAverageProcessingTime($toolType, $fileType);
        } else {
            // All tools and file types
            $toolTypes = ['summarize', 'math', 'flashcards', 'presentations', 'document_chat'];
            $fileTypes = ['pdf', 'doc', 'docx', 'txt', 'audio', 'video', 'image'];
            
            foreach ($toolTypes as $tool) {
                $stats['by_tool_type'][$tool] = [
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                    'success_rate' => 0.0
                ];
                
                foreach ($fileTypes as $file) {
                    $total = $this->getCounter("metrics:daily:{$date}:{$tool}:{$file}:total");
                    $success = $this->getCounter("metrics:daily:{$date}:{$tool}:{$file}:success");
                    $failed = $this->getCounter("metrics:daily:{$date}:{$tool}:{$file}:failed");
                    
                    $stats['by_tool_type'][$tool]['total'] += $total;
                    $stats['by_tool_type'][$tool]['success'] += $success;
                    $stats['by_tool_type'][$tool]['failed'] += $failed;
                    
                    $stats['total_processed'] += $total;
                    $stats['successful'] += $success;
                    $stats['failed'] += $failed;
                }
                
                $toolTotal = $stats['by_tool_type'][$tool]['total'];
                if ($toolTotal > 0) {
                    $stats['by_tool_type'][$tool]['success_rate'] = 
                        ($stats['by_tool_type'][$tool]['success'] / $toolTotal) * 100;
                }
            }
            
            $stats['success_rate'] = $stats['total_processed'] > 0 ? 
                ($stats['successful'] / $stats['total_processed']) * 100 : 0;
        }
        
        return $stats;
    }

    /**
     * Get hourly statistics
     */
    private function getHourlyStats($date, $toolType = null, $fileType = null)
    {
        $stats = [];
        
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $stats[$hourStr] = [
                'hour' => $hourStr,
                'total_processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'success_rate' => 0.0
            ];
            
            if ($toolType && $fileType) {
                $total = $this->getCounter("metrics:hourly:{$date}:{$hourStr}:{$toolType}:{$fileType}:total");
                $success = $this->getCounter("metrics:hourly:{$date}:{$hourStr}:{$toolType}:{$fileType}:success");
                $failed = $this->getCounter("metrics:hourly:{$date}:{$hourStr}:{$toolType}:{$fileType}:failed");
                
                $stats[$hourStr]['total_processed'] = $total;
                $stats[$hourStr]['successful'] = $success;
                $stats[$hourStr]['failed'] = $failed;
                $stats[$hourStr]['success_rate'] = $total > 0 ? ($success / $total) * 100 : 0;
            }
        }
        
        return $stats;
    }

    /**
     * Get weekly statistics
     */
    private function getWeeklyStats($toolType = null, $fileType = null)
    {
        $stats = [];
        $startDate = now()->subDays(7);
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->addDays($i)->format('Y-m-d');
            $stats[$date] = $this->getDailyStats($date, $toolType, $fileType);
        }
        
        return $stats;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($toolType = null, $fileType = null)
    {
        $metrics = [
            'average_processing_time' => $this->getAverageProcessingTime($toolType, $fileType),
            'fastest_processing_time' => $this->getFastestProcessingTime($toolType, $fileType),
            'slowest_processing_time' => $this->getSlowestProcessingTime($toolType, $fileType),
            'total_files_processed' => $this->getTotalFilesProcessed($toolType, $fileType),
            'success_rate' => $this->getSuccessRate($toolType, $fileType),
            'error_rate' => $this->getErrorRate($toolType, $fileType),
            'popular_file_types' => $this->getPopularFileTypes($toolType),
            'popular_tools' => $this->getPopularTools($fileType)
        ];
        
        return $metrics;
    }

    /**
     * Get system health status
     */
    public function getSystemHealth()
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'recommendations' => [],
            'metrics' => []
        ];
        
        // Check success rates
        $successRate = $this->getSuccessRate();
        if ($successRate < 90) {
            $health['status'] = 'warning';
            $health['issues'][] = "Low success rate: {$successRate}%";
            $health['recommendations'][] = "Investigate failed processing jobs";
        }
        
        // Check processing times
        $avgTime = $this->getAverageProcessingTime();
        if ($avgTime > 300) { // 5 minutes
            $health['status'] = 'warning';
            $health['issues'][] = "High average processing time: {$avgTime}s";
            $health['recommendations'][] = "Consider optimizing file processing pipeline";
        }
        
        // Check error rates
        $errorRate = $this->getErrorRate();
        if ($errorRate > 10) {
            $health['status'] = 'critical';
            $health['issues'][] = "High error rate: {$errorRate}%";
            $health['recommendations'][] = "Immediate attention required for error handling";
        }
        
        $health['metrics'] = [
            'success_rate' => $successRate,
            'average_processing_time' => $avgTime,
            'error_rate' => $errorRate,
            'total_files_processed' => $this->getTotalFilesProcessed()
        ];
        
        return $health;
    }

    /**
     * Helper methods
     */
    private function incrementCounter($key)
    {
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, 86400 * 30); // 30 days TTL
    }

    private function getCounter($key)
    {
        return Cache::get($key, 0);
    }

    private function recordProcessingTime($toolType, $fileType, $time)
    {
        $key = "processing_times:{$toolType}:{$fileType}";
        $times = Cache::get($key, []);
        $times[] = $time;
        
        // Keep only last 1000 entries
        if (count($times) > 1000) {
            $times = array_slice($times, -1000);
        }
        
        Cache::put($key, $times, 86400 * 30);
    }

    private function recordMetadata($toolType, $fileType, $metadata)
    {
        $key = "metadata:{$toolType}:{$fileType}";
        $existing = Cache::get($key, []);
        $existing[] = [
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata
        ];
        
        // Keep only last 100 entries
        if (count($existing) > 100) {
            $existing = array_slice($existing, -100);
        }
        
        Cache::put($key, $existing, 86400 * 30);
    }

    private function getAverageProcessingTime($toolType = null, $fileType = null)
    {
        if ($toolType && $fileType) {
            $key = "processing_times:{$toolType}:{$fileType}";
        } else {
            $key = "processing_times:all:all";
        }
        
        $times = Cache::get($key, []);
        return count($times) > 0 ? array_sum($times) / count($times) : 0;
    }

    private function getFastestProcessingTime($toolType = null, $fileType = null)
    {
        if ($toolType && $fileType) {
            $key = "processing_times:{$toolType}:{$fileType}";
        } else {
            $key = "processing_times:all:all";
        }
        
        $times = Cache::get($key, []);
        return count($times) > 0 ? min($times) : 0;
    }

    private function getSlowestProcessingTime($toolType = null, $fileType = null)
    {
        if ($toolType && $fileType) {
            $key = "processing_times:{$toolType}:{$fileType}";
        } else {
            $key = "processing_times:all:all";
        }
        
        $times = Cache::get($key, []);
        return count($times) > 0 ? max($times) : 0;
    }

    private function getTotalFilesProcessed($toolType = null, $fileType = null)
    {
        $date = now()->format('Y-m-d');
        
        if ($toolType && $fileType) {
            return $this->getCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:total");
        } else {
            $total = 0;
            $toolTypes = ['summarize', 'math', 'flashcards', 'presentations', 'document_chat'];
            $fileTypes = ['pdf', 'doc', 'docx', 'txt', 'audio', 'video', 'image'];
            
            foreach ($toolTypes as $tool) {
                foreach ($fileTypes as $file) {
                    $total += $this->getCounter("metrics:daily:{$date}:{$tool}:{$file}:total");
                }
            }
            
            return $total;
        }
    }

    private function getSuccessRate($toolType = null, $fileType = null)
    {
        $date = now()->format('Y-m-d');
        
        if ($toolType && $fileType) {
            $total = $this->getCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:total");
            $success = $this->getCounter("metrics:daily:{$date}:{$toolType}:{$fileType}:success");
            return $total > 0 ? ($success / $total) * 100 : 0;
        } else {
            $total = $this->getTotalFilesProcessed();
            $successful = 0;
            $toolTypes = ['summarize', 'math', 'flashcards', 'presentations', 'document_chat'];
            $fileTypes = ['pdf', 'doc', 'docx', 'txt', 'audio', 'video', 'image'];
            
            foreach ($toolTypes as $tool) {
                foreach ($fileTypes as $file) {
                    $successful += $this->getCounter("metrics:daily:{$date}:{$tool}:{$file}:success");
                }
            }
            
            return $total > 0 ? ($successful / $total) * 100 : 0;
        }
    }

    private function getErrorRate($toolType = null, $fileType = null)
    {
        return 100 - $this->getSuccessRate($toolType, $fileType);
    }

    private function getPopularFileTypes($toolType = null)
    {
        $date = now()->format('Y-m-d');
        $fileTypes = ['pdf', 'doc', 'docx', 'txt', 'audio', 'video', 'image'];
        $popular = [];
        
        foreach ($fileTypes as $file) {
            if ($toolType) {
                $count = $this->getCounter("metrics:daily:{$date}:{$toolType}:{$file}:total");
            } else {
                $count = 0;
                $toolTypes = ['summarize', 'math', 'flashcards', 'presentations', 'document_chat'];
                foreach ($toolTypes as $tool) {
                    $count += $this->getCounter("metrics:daily:{$date}:{$tool}:{$file}:total");
                }
            }
            
            if ($count > 0) {
                $popular[$file] = $count;
            }
        }
        
        arsort($popular);
        return $popular;
    }

    private function getPopularTools($fileType = null)
    {
        $date = now()->format('Y-m-d');
        $toolTypes = ['summarize', 'math', 'flashcards', 'presentations', 'document_chat'];
        $popular = [];
        
        foreach ($toolTypes as $tool) {
            if ($fileType) {
                $count = $this->getCounter("metrics:daily:{$date}:{$tool}:{$fileType}:total");
            } else {
                $count = 0;
                $fileTypes = ['pdf', 'doc', 'docx', 'txt', 'audio', 'video', 'image'];
                foreach ($fileTypes as $file) {
                    $count += $this->getCounter("metrics:daily:{$date}:{$tool}:{$file}:total");
                }
            }
            
            if ($count > 0) {
                $popular[$tool] = $count;
            }
        }
        
        arsort($popular);
        return $popular;
    }
}



































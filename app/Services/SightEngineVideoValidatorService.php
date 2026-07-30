<?php

namespace App\Services;

class SightEngineVideoValidatorService extends SightEngineValidatorService
{
    // Video-specific thresholds (more strict due to motion content)
    private const VIDEO_THRESHOLDS = [
        // Explicit content - zero tolerance for videos
        'sexual_activity' => 0.15,       // Lower threshold for videos
        'sexual_display' => 0.25,        // Lower threshold for videos  
        'erotica' => 0.35,               // Lower threshold for videos
        'very_suggestive' => 0.5,        // Lower threshold for videos
        
        // Violence - zero tolerance
        'violence' => 0.1,
        'self_harm' => 0.1,
        
        // Blocked content
        'visibly_undressed' => 0.3,
        'nudity_art' => 0.4,
        'sextoy' => 0.2,
        'lingerie' => 0.4,               // Slightly more permissive for videos
        'male_underwear' => 0.4,
        
        // Cleavage thresholds
        'very_revealing_cleavage' => 0.4,
        'revealing_cleavage' => 0.6,
        
        // Allowed content (higher thresholds)
        'bikini' => 0.8,
        'swimwear_one_piece' => 0.8,
        'swimwear_male' => 0.8,
        'suggestive' => 0.6,             // Lower for videos
        'mildly_suggestive' => 0.8,      // Lower for videos
        'miniskirt' => 0.7,
        'minishort' => 0.7,
        'male_chest' => 0.8,
    ];

    // Percentage of frames that must violate to block entire video
    private const VIOLATION_PERCENTAGE_THRESHOLD = 0.1; // 10% of frames

    public function validateVideoContent(array $sightEngineResponse): array
    {
        $result = [
            'approved' => true,
            'blocked_reasons' => [],
            'warnings' => [],
            'content_type' => 'safe',
            'confidence' => 0,
            'frame_analysis' => [],
            'violation_summary' => []
        ];

        // Check if the response is valid
        $validStatuses = ['success', 'finished'];
        $status = $sightEngineResponse['data']['status'] ?? $sightEngineResponse['status'] ?? null;

        if (!$status || !in_array($status, $validStatuses)) {
            return [
                'approved' => false,
                'blocked_reasons' => ['API response invalid'],
                'warnings' => [],
                'content_type' => 'error',
                'confidence' => 0,
                'frame_analysis' => [],
                'violation_summary' => []
            ];
        }

        // Check if video data exists
        if (!isset($sightEngineResponse['data']['frames']) || empty($sightEngineResponse['data']['frames'])) {
            return [
                'approved' => false,
                'blocked_reasons' => ['No video frames found'],
                'warnings' => [],
                'content_type' => 'error',
                'confidence' => 0,
                'frame_analysis' => [],
                'violation_summary' => []
            ];
        }

        $frames = $sightEngineResponse['data']['frames'];
        $totalFrames = count($frames);
        $violatingFrames = 0;
        $violationTypes = [];

        // Analyze each frame
        foreach ($frames as $frameIndex => $frame) {
            $frameResult = $this->validateSingleFrame($frame, $frameIndex);
            $result['frame_analysis'][] = $frameResult;

            if (!$frameResult['approved']) {
                $violatingFrames++;
                foreach ($frameResult['blocked_reasons'] as $reason) {
                    $violationTypes[] = $reason;
                }
            }
        }

        // Calculate violation percentage
        $violationPercentage = $violatingFrames / $totalFrames;
        
        // Determine if video should be blocked
        if ($violationPercentage >= self::VIOLATION_PERCENTAGE_THRESHOLD) {
            $result['approved'] = false;
            $result['blocked_reasons'] = array_unique($violationTypes);
            $result['content_type'] = 'explicit';
            $result['confidence'] = min(0.9, $violationPercentage + 0.1);
        } else {
            // Video passes but check for warnings
            $result = $this->categorizeVideoContent($frames, $result, $violationPercentage);
        }

        // Add violation summary
        $result['violation_summary'] = [
            'total_frames' => $totalFrames,
            'violating_frames' => $violatingFrames,
            'violation_percentage' => round($violationPercentage * 100, 1),
            'threshold_percentage' => self::VIOLATION_PERCENTAGE_THRESHOLD * 100
        ];

        return $result;
    }

    private function validateSingleFrame(array $frame, int $frameIndex): array
    {
        $frameResult = [
            'frame_index' => $frameIndex,
            'position' => $frame['info']['position'] ?? 0,
            'approved' => true,
            'blocked_reasons' => [],
            'warnings' => []
        ];

        // Check violence first
        if (isset($frame['violence']['prob'])) {
            $violenceProb = $frame['violence']['prob'];
            if ($violenceProb > self::VIDEO_THRESHOLDS['violence']) {
                $frameResult['approved'] = false;
                $frameResult['blocked_reasons'][] = "Violence detected in frame $frameIndex (probability: " . round($violenceProb * 100, 1) . "%)";
            }
        }

        $nudityData = $frame['nudity'] ?? [];

        // Check explicit sexual content
        $explicitChecks = [
            'sexual_activity' => 'Sexual activity',
            'sexual_display' => 'Sexual display', 
            'erotica' => 'Erotic content'
        ];

        foreach ($explicitChecks as $key => $description) {
            if (isset($nudityData[$key]) && $nudityData[$key] > self::VIDEO_THRESHOLDS[$key]) {
                $frameResult['approved'] = false;
                $frameResult['blocked_reasons'][] = "$description detected in frame $frameIndex (probability: " . round($nudityData[$key] * 100, 1) . "%)";
            }
        }

        // Check very suggestive content
        if (isset($nudityData['very_suggestive']) && $nudityData['very_suggestive'] > self::VIDEO_THRESHOLDS['very_suggestive']) {
            $frameResult['approved'] = false;
            $frameResult['blocked_reasons'][] = "Very suggestive content detected in frame $frameIndex (probability: " . round($nudityData['very_suggestive'] * 100, 1) . "%)";
        }

        // Check specific nudity classes
        $suggestiveClasses = $nudityData['suggestive_classes'] ?? [];
        
        $blockedItems = [
            'visibly_undressed' => 'Visibly undressed',
            'nudity_art' => 'Artistic nudity',
            'sextoy' => 'Sexual content',
            'lingerie' => 'Lingerie'
        ];

        foreach ($blockedItems as $key => $description) {
            if (isset($suggestiveClasses[$key]) && $suggestiveClasses[$key] > self::VIDEO_THRESHOLDS[$key]) {
                $frameResult['approved'] = false;
                $frameResult['blocked_reasons'][] = "$description detected in frame $frameIndex (probability: " . round($suggestiveClasses[$key] * 100, 1) . "%)";
            }
        }

        return $frameResult;
    }

    private function categorizeVideoContent(array $frames, array $result, float $violationPercentage): array
    {
        // If some frames have violations but below threshold, add warnings
        if ($violationPercentage > 0) {
            $result['content_type'] = 'suggestive_video';
            $result['warnings'][] = "Video contains " . round($violationPercentage * 100, 1) . "% frames with suggestive content";
            $result['confidence'] = 0.6;
        } else {
            // Check if video contains allowed suggestive content
            $suggestiveFrames = 0;
            $totalFrames = count($frames);

            foreach ($frames as $frame) {
                $nudityData = $frame['nudity'] ?? [];
                if (($nudityData['suggestive'] ?? 0) > 0.3 || ($nudityData['mildly_suggestive'] ?? 0) > 0.3) {
                    $suggestiveFrames++;
                }
            }

            if ($suggestiveFrames > 0) {
                $result['content_type'] = 'mildly_suggestive_video';
                $result['confidence'] = 0.8;
            } else {
                $result['content_type'] = 'safe_video';
                $result['confidence'] = 0.95;
            }
        }

        return $result;
    }

    // Method to get worst frame for debugging
    public function getWorstFrame(array $sightEngineResponse): ?array
    {
        if (!isset($sightEngineResponse['data']['frames'])) {
            return null;
        }

        $worstFrame = null;
        $highestViolation = 0;

        foreach ($sightEngineResponse['data']['frames'] as $frame) {
            $nudityData = $frame['nudity'] ?? [];
            $violationScore = max(
                $nudityData['sexual_activity'] ?? 0,
                $nudityData['sexual_display'] ?? 0,
                $nudityData['erotica'] ?? 0,
                $nudityData['very_suggestive'] ?? 0
            );

            if ($violationScore > $highestViolation) {
                $highestViolation = $violationScore;
                $worstFrame = $frame;
            }
        }

        return $worstFrame;
    }
}
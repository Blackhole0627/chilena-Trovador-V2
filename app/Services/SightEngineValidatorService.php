<?php 

namespace App\Services;

class SightEngineValidatorService
{
    // Threshold configuration
    private const THRESHOLDS = [
        // Completely blocked content
        'sexual_activity' => 0.2,        // Explicit sexual activity
        'sexual_display' => 0.3,         // Sexual display
        'erotica' => 0.4,                // Erotic content
        'very_suggestive' => 0.6,        // Very suggestive (block)
        
        // Violence - zero tolerance
        'violence' => 0.1,               // Any type of violence
        'self_harm' => 0.1,              // Self-harm
        
        // Specific nudity - blocked
        'visibly_undressed' => 0.3,      // Visibly undressed
        'nudity_art' => 0.4,             // Artistic nudity
        'sextoy' => 0.2,                 // Sexual content/toys
        
        // Underwear - more restrictive
        'lingerie' => 0.5,               // Lingerie
        'male_underwear' => 0.5,         // Male underwear
        
        // Very revealing cleavage
        'very_revealing_cleavage' => 0.4, // Very revealing cleavage
        'revealing_cleavage' => 0.6,      // Revealing cleavage
        
        // Allowed suggestive content (higher thresholds)
        'bikini' => 0.8,                 // Bikinis allowed
        'swimwear_one_piece' => 0.8,     // One-piece swimwear
        'swimwear_male' => 0.8,          // Male swimwear
        'suggestive' => 0.7,             // General suggestive content
        'mildly_suggestive' => 0.9,      // Mildly suggestive
        'miniskirt' => 0.7,              // Miniskirts
        'minishort' => 0.7,              // Short shorts
        'male_chest' => 0.8,             // Male chest (beach, sports)
    ];

    public function validateContent(array $sightEngineResponse): array
    {
        $result = [
            'approved' => true,
            'blocked_reasons' => [],
            'warnings' => [],
            'content_type' => 'safe',
            'confidence' => 0
        ];

        // Check if the response is valid
        if (!isset($sightEngineResponse['status']) || $sightEngineResponse['status'] !== 'success') {
            return [
                'approved' => false,
                'blocked_reasons' => ['API response invalid'],
                'warnings' => [],
                'content_type' => 'error',
                'confidence' => 0
            ];
        }

        // 1. Check violence (zero tolerance)
        if (isset($sightEngineResponse['violence']['prob'])) {
            $violenceProb = $sightEngineResponse['violence']['prob'];
            if ($violenceProb > self::THRESHOLDS['violence']) {
                $result['approved'] = false;
                $result['blocked_reasons'][] = "Violence detected (probability: " . round($violenceProb * 100, 1) . "%)";
            }
        }

        // 2. Check self-harm
        if (isset($sightEngineResponse['self-harm']['prob'])) {
            $selfHarmProb = $sightEngineResponse['self-harm']['prob'];
            if ($selfHarmProb > self::THRESHOLDS['self_harm']) {
                $result['approved'] = false;
                $result['blocked_reasons'][] = "Self-harm content detected (probability: " . round($selfHarmProb * 100, 1) . "%)";
            }
        }

        // 3. Check explicit sexual content
        $nudityData = $sightEngineResponse['nudity'] ?? [];
        
        // Explicit sexual content - block
        $explicitChecks = [
            'sexual_activity' => 'Sexual activity',
            'sexual_display' => 'Sexual display',
            'erotica' => 'Erotic content'
        ];
        
        foreach ($explicitChecks as $key => $description) {
            if (isset($nudityData[$key]) && $nudityData[$key] > self::THRESHOLDS[$key]) {
                $result['approved'] = false;
                $result['blocked_reasons'][] = "$description detected (probability: " . round($nudityData[$key] * 100, 1) . "%)";
            }
        }

        // 4. Check suggestive content level
        if (isset($nudityData['very_suggestive']) && $nudityData['very_suggestive'] > self::THRESHOLDS['very_suggestive']) {
            $result['approved'] = false;
            $result['blocked_reasons'][] = "Very suggestive content detected (probability: " . round($nudityData['very_suggestive'] * 100, 1) . "%)";
        }

        // 5. Check specific nudity classes
        $suggestiveClasses = $nudityData['suggestive_classes'] ?? [];
        
        // Check completely blocked content
        $blockedItems = [
            'visibly_undressed' => 'Visibly undressed',
            'nudity_art' => 'Artistic nudity',
            'sextoy' => 'Sexual content'
        ];
        
        foreach ($blockedItems as $key => $description) {
            if (isset($suggestiveClasses[$key]) && $suggestiveClasses[$key] > self::THRESHOLDS[$key]) {
                $result['approved'] = false;
                $result['blocked_reasons'][] = "$description detected (probability: " . round($suggestiveClasses[$key] * 100, 1) . "%)";
            }
        }

        // 6. Check very revealing cleavage
        $cleavageCategories = $suggestiveClasses['cleavage_categories'] ?? [];
        if (isset($cleavageCategories['very_revealing']) && $cleavageCategories['very_revealing'] > self::THRESHOLDS['very_revealing_cleavage']) {
            $result['approved'] = false;
            $result['blocked_reasons'][] = "Very revealing cleavage detected (probability: " . round($cleavageCategories['very_revealing'] * 100, 1) . "%)";
        }

        // 7. Check lingerie (more restrictive)
        if (isset($suggestiveClasses['lingerie']) && $suggestiveClasses['lingerie'] > self::THRESHOLDS['lingerie']) {
            $result['approved'] = false;
            $result['blocked_reasons'][] = "Lingerie detected (probability: " . round($suggestiveClasses['lingerie'] * 100, 1) . "%)";
        }

        // 8. Check allowed suggestive content and categorize
        if ($result['approved']) {
            $result = $this->categorizeAllowedContent($nudityData, $result);
        }

        return $result;
    }

    private function categorizeAllowedContent(array $nudityData, array $result): array
    {
        $suggestiveClasses = $nudityData['suggestive_classes'] ?? [];
        $context = $nudityData['context'] ?? [];
        
        // Check if it's beach/pool context
        $isBeachPoolContext = (
            ($context['sea_lake_pool'] ?? 0) > 0.3 || 
            ($context['outdoor_other'] ?? 0) > 0.3
        );
        
        // Check allowed suggestive content using THRESHOLDS
        if (($suggestiveClasses['bikini'] ?? 0) > self::THRESHOLDS['bikini'] || 
            ($suggestiveClasses['swimwear_one_piece'] ?? 0) > self::THRESHOLDS['swimwear_one_piece'] || 
            ($suggestiveClasses['swimwear_male'] ?? 0) > self::THRESHOLDS['swimwear_male']) {
            
            $result['content_type'] = 'swimwear';
            $result['warnings'][] = 'Swimwear detected - appropriate for beach/pool context';
            
        } elseif (($nudityData['suggestive'] ?? 0) > self::THRESHOLDS['suggestive']) {
            $result['content_type'] = 'suggestive';
            $result['warnings'][] = 'Suggestive content detected but within acceptable limits';
            
        } elseif (($nudityData['mildly_suggestive'] ?? 0) > self::THRESHOLDS['mildly_suggestive']) {
            $result['content_type'] = 'mildly_suggestive';
            
        } else {
            $result['content_type'] = 'safe';
        }
        
        // Calculate confidence based on context
        if ($isBeachPoolContext && $result['content_type'] === 'swimwear') {
            $result['confidence'] = 0.9; // High confidence for swimwear in appropriate context
        } elseif ($result['content_type'] === 'safe') {
            $result['confidence'] = 0.95;
        } else {
            $result['confidence'] = 0.7;
        }
        
        return $result;
    }

    // Utility method for use in controllers
    public function isContentAllowed(array $sightEngineResponse): bool
    {
        return $this->validateContent($sightEngineResponse)['approved'];
    }

    // Method to get only the blocking reasons
    public function getBlockedReasons(array $sightEngineResponse): array
    {
        return $this->validateContent($sightEngineResponse)['blocked_reasons'];
    }
}
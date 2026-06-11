<?php
// Game helpers - category generation, smart randomization, gem management

/**
 * Generate category options for the current round with smart grouping
 * Returns array with 'free' category and 'paid' categories organized by type
 */
function generateCategories($sessionId, $db) {
    // Free category - always available
    $free = ['name' => '1-20', 'label' => 'Full Range', 'description' => '1 - 20', 'cost' => 0, 'reward' => 10, 'type' => 'free'];
    
    // Parity category group (Even/Odd)
    $parityGroup = [
        ['name' => 'even', 'label' => 'Even Numbers', 'description' => '2, 4, 6, 8, 10, 12, 14, 16, 18, 20', 'cost' => 10, 'reward' => 20, 'type' => 'parity'],
        ['name' => 'odd', 'label' => 'Odd Numbers', 'description' => '1, 3, 5, 7, 9, 11, 13, 15, 17, 19', 'cost' => 10, 'reward' => 20, 'type' => 'parity'],
    ];
    
    // Range category group (1-5, 6-14, 15-20)
    $rangeGroup = [
        ['name' => '1-5', 'label' => 'Low Range', 'description' => '1 - 5', 'cost' => 10, 'reward' => 20, 'type' => 'range'],
        ['name' => '6-14', 'label' => 'Mid Range', 'description' => '6 - 14', 'cost' => 10, 'reward' => 20, 'type' => 'range'],
        ['name' => '15-20', 'label' => 'High Range', 'description' => '15 - 20', 'cost' => 10, 'reward' => 20, 'type' => 'range'],
    ];
    
    // Randomly choose which paid categories to offer this round
    $offerEvenOdd = rand(0, 1) === 1;
    
    $paidCategories = [];
    if ($offerEvenOdd) {
        // Offer Both Even and Odd this round (parity group)
        $paidCategories = $parityGroup;
    } else {
        // Offer 2 random ranges from range group
        $randomRanges = array_rand($rangeGroup, 2);
        $paidCategories = [
            $rangeGroup[$randomRanges[0]],
            $rangeGroup[$randomRanges[1]]
        ];
    }
    
    // Return organized structure
    return [
        'free' => $free,
        'paid' => $paidCategories
    ];
}

/**
 * Get the list of disabled numbers for the current session
 * Returns array of disabled numbers, or empty array if none
 */
function getDisabledNumbers($sessionId, $db) {
    $stmt = $db->prepare('SELECT disabled_numbers, round_disabled_at FROM game_sessions WHERE id = ?');
    $stmt->execute([$sessionId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !$result['disabled_numbers']) {
        return [];
    }
    
    return json_decode($result['disabled_numbers'], true) ?? [];
}

/**
 * Calculate which numbers should be disabled at a given round
 * Pattern: 3 normal rounds, 1 disabled round (cycling)
 * Rounds 1-3: normal, Round 4: disabled, Rounds 5-7: normal, Round 8: disabled, etc.
 */
function calculateDisabledNumbers($guessCount) {
    // Calculate current round (each round = 1 guess per player, so guessCount / 2)
    $currentRound = ceil(($guessCount + 1) / 2);
    
    // Determine position in 4-round cycle (1, 2, 3, or 4)
    // Round 1,2,3,4,5,6,7,8 → cyclePosition 1,2,3,4,1,2,3,4
    $cyclePosition = (($currentRound - 1) % 4) + 1;
    
    // Only disable at cycle position 4
    if ($cyclePosition != 4) {
        return null; // Don't disable
    }
    
    // Generate 3-5 random numbers to disable (1-20)
    $allNumbers = range(1, 20);
    shuffle($allNumbers);
    $disabledCount = rand(3, 5);
    
    // Return the first 3-5 numbers from shuffled array
    return array_slice($allNumbers, 0, $disabledCount);
}

/**
 * Get available numbers for secret number generation
 * Returns array of numbers that can be generated (1-20 minus disabled)
 */
function getAvailableNumbers($disabledNumbers = []) {
    $allNumbers = range(1, 20);
    if (empty($disabledNumbers)) {
        return $allNumbers;
    }
    return array_values(array_diff($allNumbers, $disabledNumbers));
}

/**
 * Generate smart randomized secret number with optional pattern strategy
 * Patterns: 'default' (70% alternation) or 'adaptive' (intelligent round-based)
 */
function generateSmartRandomNumber($lastSecretNumber = null, $availableNumbers = null, $patternMode = 'default', $gameState = null) {
    if (!$availableNumbers) {
        $availableNumbers = range(1, 20);
    }
    
    if ($patternMode === 'adaptive' && $gameState) {
        return generateByAdaptivePattern($lastSecretNumber, $availableNumbers, $gameState);
    }
    
    // DEFAULT: 70% weighted alternation
    if ($lastSecretNumber === null || count($availableNumbers) < 2) {
        return $availableNumbers[array_rand($availableNumbers)];
    }
    
    $filtered = array_diff($availableNumbers, [$lastSecretNumber]);
    
    if (empty($filtered)) {
        return $lastSecretNumber;
    }
    
    if (rand(1, 100) <= 70) {
        $target_range = ($lastSecretNumber <= 10) ? 
            array_filter($filtered, fn($n) => $n > 10) :
            array_filter($filtered, fn($n) => $n <= 10);
        
        if (!empty($target_range)) {
            $filtered = $target_range;
        }
    }
    
    return $filtered[array_rand($filtered)];
}

/**
 * PATTERN 1: Quartile Cycling
 * Divides 1-20 into 4 zones: [1-5, 6-10, 11-15, 16-20]
 * Cycles through them sequentially
 */
function generateQuartileCycling($lastSecretNumber = null, $availableNumbers = null, $guessCount = 0) {
    if (!$availableNumbers) {
        $availableNumbers = range(1, 20);
    }
    
    $quartiles = [
        [1, 2, 3, 4, 5],
        [6, 7, 8, 9, 10],
        [11, 12, 13, 14, 15],
        [16, 17, 18, 19, 20]
    ];
    
    $quartileIndex = $guessCount % 4;
    $selectedQuartile = array_intersect($quartiles[$quartileIndex], $availableNumbers);
    
    if (empty($selectedQuartile)) {
        return $availableNumbers[array_rand($availableNumbers)];
    }
    
    return $selectedQuartile[array_rand($selectedQuartile)];
}

/**
 * PATTERN 2: Distance Progression
 * Prefers numbers far from the last secret number
 * Creates spiraling effect away from previous guesses
 */
function generateDistanceProgression($lastSecretNumber = null, $availableNumbers = null, $guessCount = 0) {
    if (!$availableNumbers) {
        $availableNumbers = range(1, 20);
    }
    
    if ($lastSecretNumber === null) {
        return $availableNumbers[array_rand($availableNumbers)];
    }
    
    $candidatesHigh = array_filter($availableNumbers, function($n) use ($lastSecretNumber) {
        return $n > ($lastSecretNumber + 3);
    });
    
    $candidatesLow = array_filter($availableNumbers, function($n) use ($lastSecretNumber) {
        return $n < ($lastSecretNumber - 3);
    });
    
    $candidates = array_merge($candidatesHigh, $candidatesLow);
    
    if (!empty($candidates)) {
        return $candidates[array_rand($candidates)];
    }
    
    return ($lastSecretNumber <= 10) ? 20 : 1;
}

/**
 * PATTERN 3: Proximity to Disabled
 * Gravitates toward numbers adjacent to disabled ones
 * Makes disabled rounds (4/8/12) significantly harder
 */
function generateProximityToDisabled($lastSecretNumber = null, $availableNumbers = null, $disabledNumbers = []) {
    if (empty($disabledNumbers)) {
        return $availableNumbers[array_rand($availableNumbers)];
    }
    
    $nearDisabled = [];
    foreach ($disabledNumbers as $disabled) {
        if ($disabled > 1) $nearDisabled[] = $disabled - 1;
        if ($disabled < 20) $nearDisabled[] = $disabled + 1;
    }
    $nearDisabled = array_unique($nearDisabled);
    
    $candidates = array_intersect($nearDisabled, $availableNumbers);
    
    if (!empty($candidates)) {
        return $candidates[array_rand($candidates)];
    }
    
    return $availableNumbers[array_rand($availableNumbers)];
}

/**
 * Adaptive Pattern Selector
 * Intelligently chooses generation pattern based on game state:
 * - Rounds 1-2: Random (baseline learning)
 * - Rounds 3-5: Quartile cycling (teach zones)
 * - Rounds 6+: Distance progression (skill challenge)
 * - Disabled rounds: Proximity (difficulty spike)
 */
function generateByAdaptivePattern($lastSecretNumber = null, $availableNumbers = null, $gameState = []) {
    if (!$availableNumbers) {
        $availableNumbers = range(1, 20);
    }
    
    $guessCount = $gameState['guess_count'] ?? 0;
    $currentRound = ceil(($guessCount + 1) / 2);
    $isDisabledRound = $gameState['is_disabled_round'] ?? false;
    $disabledNumbers = $gameState['disabled_numbers'] ?? [];
    
    // Disabled round: Use proximity pattern (harder)
    if ($isDisabledRound && !empty($disabledNumbers)) {
        return generateProximityToDisabled($lastSecretNumber, $availableNumbers, $disabledNumbers);
    }
    
    // Early rounds (1-2): Random
    if ($currentRound <= 2) {
        if ($lastSecretNumber === null || count($availableNumbers) < 2) {
            return $availableNumbers[array_rand($availableNumbers)];
        }
        $filtered = array_diff($availableNumbers, [$lastSecretNumber]);
        return !empty($filtered) ? $filtered[array_rand($filtered)] : $lastSecretNumber;
    }
    
    // Mid rounds (3-5): Quartile cycling
    if ($currentRound <= 5) {
        return generateQuartileCycling($lastSecretNumber, $availableNumbers, $guessCount);
    }
    
    // Later rounds (6+): Distance progression
    return generateDistanceProgression($lastSecretNumber, $availableNumbers, $guessCount);
}

/**
 * Validate if a guess is in the selected category
 */
function isInCategory($guessNumber, $category) {
    if ($category === '1-20') {
        return $guessNumber >= 1 && $guessNumber <= 20;
    }
    
    if ($category === 'odd') {
        return $guessNumber % 2 == 1;
    }
    
    if ($category === 'even') {
        return $guessNumber % 2 == 0;
    }
    
    // Handle range categories like '1-6', '7-13', etc.
    if (strpos($category, '-') !== false) {
        list($low, $high) = explode('-', $category);
        $low = (int)$low;
        $high = (int)$high;
        return $guessNumber >= $low && $guessNumber <= $high;
    }
    
    return false;
}

/**
 * Get available numbers for a specific category (for validation/UI)
 */
function getCategoryNumbers($category) {
    if ($category === '1-20') {
        return range(1, 20);
    }
    
    if ($category === 'odd') {
        return [1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
    }
    
    if ($category === 'even') {
        return [2, 4, 6, 8, 10, 12, 14, 16, 18, 20];
    }
    
    // Handle range categories
    if (strpos($category, '-') !== false) {
        list($low, $high) = explode('-', $category);
        return range((int)$low, (int)$high);
    }
    
    return [];
}

<?php

namespace App\Services\Intelligence;

use App\Models\ScrapedItem;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * EntityExtractor - Extracts structured entities from content
 * 
 * Extracts: organizations, people, locations, dates, money, emails, phones, URLs
 * Uses both rule-based extraction and AI for maximum accuracy
 */
class EntityExtractor
{
    protected OpenAIService $aiService;

    /**
     * Kenya-specific patterns and data
     */
    protected array $kenyaCounties = [
        'Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi',
        'Kitale', 'Garissa', 'Kakamega', 'Nyeri', 'Meru', 'Kiambu', 'Machakos',
        'Uasin Gishu', 'Trans Nzoia', 'Kilifi', 'Kwale', 'Bungoma', 'Kisii',
    ];

    protected array $governmentMinistries = [
        'Ministry of Health',
        'Ministry of Education',
        'Ministry of Transport',
        'Ministry of Agriculture',
        'Ministry of Energy',
        'Ministry of ICT',
        'Ministry of Interior',
        'Ministry of Treasury',
        'Ministry of Defence',
        'Ministry of Foreign Affairs',
    ];

    public function __construct(OpenAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Extract all entities from a scraped item
     */
    public function extract(ScrapedItem $item): array
    {
        // Check cache
        $cacheKey = "entity_extraction_{$item->content_hash}";
        
        if ($cached = Cache::get($cacheKey)) {
            Log::info("Using cached entity extraction", ['item_id' => $item->id]);
            return $cached;
        }

        $entities = [
            'organizations' => [],
            'people' => [],
            'locations' => [],
            'dates' => [],
            'money' => [],
            'contacts' => [],
        ];

        // Rule-based extraction (fast)
        $ruleBasedEntities = $this->ruleBasedExtraction($item);
        $entities = array_merge_recursive($entities, $ruleBasedEntities);

        // AI extraction for more complex entities
        try {
            $aiEntities = $this->aiExtraction($item);
            $entities = $this->mergeEntities($entities, $aiEntities);
        } catch (\Throwable $e) {
            Log::error("AI extraction failed", [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Deduplicate and clean
        $entities = $this->cleanEntities($entities);

        Cache::put($cacheKey, $entities, now()->addDays(7));

        return $entities;
    }

    /**
     * Rule-based entity extraction
     */
    protected function ruleBasedExtraction(ScrapedItem $item): array
    {
        $text = $item->title . "\n" . $item->description . "\n" . $item->content;
        
        $entities = [
            'organizations' => [],
            'people' => [],
            'locations' => [],
            'dates' => [],
            'money' => [],
            'contacts' => [],
        ];

        // Extract emails
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $emails);
        foreach (array_unique($emails[0]) as $email) {
            $entities['contacts'][] = [
                'type' => 'email',
                'value' => strtolower($email),
                'confidence' => 0.95,
            ];
        }

        // Extract phone numbers (Kenya format)
        preg_match_all('/(?:\+254|0)[17]\d{8}/', $text, $phones);
        foreach (array_unique($phones[0]) as $phone) {
            $entities['contacts'][] = [
                'type' => 'phone',
                'value' => $this->normalizePhone($phone),
                'confidence' => 0.9,
            ];
        }

        // Extract money (KES and USD)
        preg_match_all('/(?:KES|Ksh|USD|\$)\s*[\d,]+(?:\.\d{2})?(?:\s*(?:million|billion|M|B))?/i', $text, $money);
        foreach (array_unique($money[0]) as $amount) {
            $parsed = $this->parseMoney($amount);
            if ($parsed) {
                $entities['money'][] = $parsed;
            }
        }

        // Extract dates
        preg_match_all('/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', $text, $dates);
        foreach (array_unique($dates[0]) as $date) {
            try {
                $parsed = \Carbon\Carbon::parse($date);
                $entities['dates'][] = [
                    'raw' => $date,
                    'parsed' => $parsed->format('Y-m-d'),
                    'type' => 'date',
                    'confidence' => 0.85,
                ];
            } catch (\Throwable $e) {
                // Skip invalid dates
            }
        }

        // Extract Kenya counties
        foreach ($this->kenyaCounties as $county) {
            if (stripos($text, $county) !== false) {
                $entities['locations'][] = [
                    'name' => $county,
                    'type' => 'county',
                    'country' => 'Kenya',
                    'confidence' => 0.9,
                ];
            }
        }

        // Extract government ministries
        foreach ($this->governmentMinistries as $ministry) {
            if (stripos($text, $ministry) !== false) {
                $entities['organizations'][] = [
                    'name' => $ministry,
                    'type' => 'government',
                    'confidence' => 0.95,
                ];
            }
        }

        // Extract tender numbers
        preg_match_all('/[A-Z]{2,}[-\/]\d{2,}[-\/]\d{2,}/i', $text, $tenderNumbers);
        foreach (array_unique($tenderNumbers[0]) as $tenderNum) {
            $entities['organizations'][] = [
                'name' => $tenderNum,
                'type' => 'tender_number',
                'confidence' => 0.9,
            ];
        }

        return $entities;
    }

    /**
     * AI-powered entity extraction
     */
    protected function aiExtraction(ScrapedItem $item): array
    {
        $prompt = $this->buildExtractionPrompt($item);

        $response = $this->aiService->chat([
            [
                'role' => 'system',
                'content' => 'You are an expert at extracting structured entities from text. Focus on Kenya-specific entities. Respond only with valid JSON.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ], [
            'temperature' => 0.2,
            'max_tokens' => 800,
        ]);

        $result = json_decode($response, true);

        if (!$result) {
            throw new \RuntimeException("Invalid AI extraction response");
        }

        return $result;
    }

    /**
     * Build extraction prompt
     */
    protected function buildExtractionPrompt(ScrapedItem $item): string
    {
        $title = mb_substr($item->title, 0, 200);
        $description = mb_substr($item->description ?? '', 0, 500);
        $content = mb_substr($item->content ?? '', 0, 1500);

        return <<<PROMPT
Extract structured entities from this content. Focus on Kenya-specific organizations, locations, and government entities.

Title: {$title}

Description: {$description}

Content: {$content}

Extract and respond with JSON in this exact format:
{
  "organizations": [
    {"name": "Organization Name", "type": "company|government|ngo", "confidence": 0.9}
  ],
  "people": [
    {"name": "Person Name", "title": "Job Title", "confidence": 0.85}
  ],
  "locations": [
    {"name": "Location", "type": "city|county|country", "country": "Kenya", "confidence": 0.9}
  ],
  "dates": [
    {"raw": "15/12/2024", "parsed": "2024-12-15", "type": "deadline|event|published", "confidence": 0.9}
  ],
  "money": [
    {"amount": 5000000, "currency": "KES", "formatted": "KES 5,000,000", "confidence": 0.95}
  ]
}

Only include entities you are confident about (>0.7 confidence). Return empty arrays if no entities found.
PROMPT;
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhone(string $phone): string
    {
        // Convert to +254 format
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (str_starts_with($phone, '0')) {
            $phone = '+254' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }

    /**
     * Parse money amount
     */
    protected function parseMoney(string $money): ?array
    {
        // Extract currency
        $currency = 'KES';
        if (preg_match('/USD|\$/i', $money)) {
            $currency = 'USD';
        }

        // Extract number
        preg_match('/[\d,]+(?:\.\d{2})?/', $money, $matches);
        if (empty($matches)) {
            return null;
        }

        $amount = (float) str_replace(',', '', $matches[0]);

        // Handle million/billion
        if (preg_match('/million|M/i', $money)) {
            $amount *= 1000000;
        } elseif (preg_match('/billion|B/i', $money)) {
            $amount *= 1000000000;
        }

        return [
            'amount' => $amount,
            'currency' => $currency,
            'formatted' => $currency . ' ' . number_format($amount, 2),
            'confidence' => 0.85,
        ];
    }

    /**
     * Merge entities from multiple sources
     */
    protected function mergeEntities(array $entities1, array $entities2): array
    {
        foreach ($entities2 as $type => $items) {
            if (!isset($entities1[$type])) {
                $entities1[$type] = [];
            }
            
            $entities1[$type] = array_merge($entities1[$type], $items);
        }

        return $entities1;
    }

    /**
     * Clean and deduplicate entities
     */
    protected function cleanEntities(array $entities): array
    {
        foreach ($entities as $type => &$items) {
            // Remove duplicates by name/value
            $seen = [];
            $items = array_filter($items, function ($item) use (&$seen) {
                $key = $item['name'] ?? $item['value'] ?? $item['raw'] ?? json_encode($item);
                $key = strtolower($key);
                
                if (in_array($key, $seen)) {
                    return false;
                }
                
                $seen[] = $key;
                return true;
            });

            // Sort by confidence
            usort($items, function ($a, $b) {
                return ($b['confidence'] ?? 0) <=> ($a['confidence'] ?? 0);
            });

            // Limit to top 20 per type
            $items = array_slice($items, 0, 20);
        }

        return $entities;
    }

    /**
     * Update item with extracted entities
     */
    public function updateItemWithEntities(ScrapedItem $item, array $entities): void
    {
        $metadata = $item->metadata ?? [];
        $metadata['extracted_entities'] = $entities;

        // Extract key fields for quick access
        if (!empty($entities['organizations'][0])) {
            $metadata['organization'] = $entities['organizations'][0]['name'];
        }

        if (!empty($entities['locations'][0])) {
            $metadata['location'] = $entities['locations'][0]['name'];
        }

        if (!empty($entities['contacts'])) {
            foreach ($entities['contacts'] as $contact) {
                if ($contact['type'] === 'email' && empty($metadata['email'])) {
                    $metadata['email'] = $contact['value'];
                }
                if ($contact['type'] === 'phone' && empty($metadata['phone'])) {
                    $metadata['phone'] = $contact['value'];
                }
            }
        }

        $item->metadata = $metadata;
        $item->save();

        Log::info("Updated item with extracted entities", [
            'item_id' => $item->id,
            'entity_counts' => array_map('count', $entities),
        ]);
    }

    /**
     * Batch extract entities from multiple items
     */
    public function batchExtract(array $items, int $batchSize = 10): array
    {
        $results = [];
        $chunks = array_chunk($items, $batchSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $item) {
                try {
                    $results[$item->id] = $this->extract($item);
                } catch (\Throwable $e) {
                    Log::error("Failed to extract entities", [
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $results[$item->id] = [
                        'organizations' => [],
                        'people' => [],
                        'locations' => [],
                        'dates' => [],
                        'money' => [],
                        'contacts' => [],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get entity statistics
     */
    public function getEntityStatistics(int $organizationId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $items = ScrapedItem::where('organization_id', $organizationId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('metadata')
            ->get();

        $stats = [
            'total_items' => $items->count(),
            'items_with_entities' => 0,
            'entity_counts' => [
                'organizations' => 0,
                'people' => 0,
                'locations' => 0,
                'dates' => 0,
                'money' => 0,
                'contacts' => 0,
            ],
            'top_organizations' => [],
            'top_locations' => [],
        ];

        $orgs = [];
        $locations = [];

        foreach ($items as $item) {
            $entities = $item->metadata['extracted_entities'] ?? null;
            
            if (!$entities) {
                continue;
            }

            $stats['items_with_entities']++;

            foreach ($entities as $type => $items) {
                $stats['entity_counts'][$type] += count($items);

                // Track top organizations and locations
                if ($type === 'organizations') {
                    foreach ($items as $org) {
                        $name = $org['name'] ?? null;
                        if ($name) {
                            $orgs[$name] = ($orgs[$name] ?? 0) + 1;
                        }
                    }
                }

                if ($type === 'locations') {
                    foreach ($items as $loc) {
                        $name = $loc['name'] ?? null;
                        if ($name) {
                            $locations[$name] = ($locations[$name] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        // Top 10 organizations
        arsort($orgs);
        $stats['top_organizations'] = array_slice($orgs, 0, 10, true);

        // Top 10 locations
        arsort($locations);
        $stats['top_locations'] = array_slice($locations, 0, 10, true);

        return $stats;
    }
}

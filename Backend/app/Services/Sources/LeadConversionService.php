<?php

namespace App\Services\Sources;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\ScrapedItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeadConversionService - Converts scraped items to qualified leads
 * 
 * Automatically converts high-quality scraped content (especially from
 * webhooks and tenders) into actionable leads in the CRM system.
 */
class LeadConversionService
{
    /**
     * Minimum scores required for auto-conversion
     */
    protected array $conversionThresholds = [
        'opportunity_score' => 60,
        'lead_score' => 60,
        'relevance_score' => 50,
    ];

    /**
     * Convert a scraped item to a lead
     */
    public function convertToLead(ScrapedItem $item, array $options = []): ?Lead
    {
        // Check if already converted
        if ($item->lead_id) {
            Log::info("Item already converted to lead", ['item_id' => $item->id]);
            return $item->lead;
        }

        // Check if meets conversion criteria
        if (!$this->shouldConvert($item, $options)) {
            Log::info("Item does not meet conversion criteria", [
                'item_id' => $item->id,
                'opportunity_score' => $item->opportunity_score,
                'lead_score' => $item->lead_score,
            ]);
            return null;
        }

        DB::beginTransaction();
        try {
            // Extract lead data
            $leadData = $this->extractLeadData($item);

            // Create or find contact
            $contact = $this->findOrCreateContact($item, $leadData);

            // Create lead
            $lead = Lead::create([
                'organization_id' => $item->organization_id,
                'name' => $leadData['contact_name'] ?? $leadData['company'] ?? 'Unknown Lead',
                'email' => $leadData['email'],
                'phone' => $leadData['phone'],
                'location' => $leadData['location'],
                'company' => $leadData['company'],
                'source' => $this->determineSource($item),
                'status' => 'new',
                'intent' => $item->intent,
                'lead_score' => $item->lead_score ?? 0,
                'score_label' => $this->determineQuality($item),
                'score_breakdown' => [
                    'opportunity_score' => $item->opportunity_score,
                    'lead_score' => $item->lead_score,
                    'relevance_score' => $item->relevance_score,
                ],
                'score_explanation' => $this->generateScoreExplanation($item),
                'scored_at' => now(),
                'message' => $leadData['description'],
            ]);

            // Create contact if we have contact info
            if (!$contact && (!empty($leadData['email']) || !empty($leadData['phone']))) {
                $contact = Contact::create([
                    'organization_id' => $item->organization_id,
                    'lead_id' => $lead->id,
                    'name' => $leadData['contact_name'] ?? $leadData['company'] ?? 'Unknown',
                    'email' => $leadData['email'],
                    'phone' => $leadData['phone'],
                    'company' => $leadData['company'],
                ]);
            } elseif ($contact && !$contact->lead_id) {
                // Link existing contact to this lead
                $contact->lead_id = $lead->id;
                $contact->save();
            }

            // Link item to lead
            $item->lead_id = $lead->id;
            $item->processing_status = 'converted';
            $item->save();

            DB::commit();

            Log::info("Successfully converted item to lead", [
                'item_id' => $item->id,
                'lead_id' => $lead->id,
            ]);

            return $lead;
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error("Failed to convert item to lead", [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if item should be converted to lead
     */
    protected function shouldConvert(ScrapedItem $item, array $options): bool
    {
        // Manual conversion always allowed
        if ($options['force'] ?? false) {
            return true;
        }

        // Must be processed
        if ($item->processing_status === 'pending') {
            return false;
        }

        // Check intent - transactional and tender are high priority
        if (in_array($item->intent, ['transactional', 'tender'])) {
            // Lower threshold for transactional intents
            return $item->lead_score >= 40 || $item->opportunity_score >= 50;
        }

        // Commercial intent
        if ($item->intent === 'commercial') {
            return $item->lead_score >= $this->conversionThresholds['lead_score'];
        }

        // Check all thresholds for other intents
        return $item->opportunity_score >= $this->conversionThresholds['opportunity_score']
            && $item->lead_score >= $this->conversionThresholds['lead_score']
            && $item->relevance_score >= $this->conversionThresholds['relevance_score'];
    }

    /**
     * Extract lead data from scraped item
     */
    protected function extractLeadData(ScrapedItem $item): array
    {
        $metadata = $item->metadata ?? [];

        return [
            'title' => $this->generateLeadTitle($item),
            'description' => $this->generateLeadDescription($item),
            'contact_name' => $metadata['contact_name'] ?? null,
            'email' => $metadata['email'] ?? null,
            'phone' => $metadata['phone'] ?? null,
            'company' => $metadata['company'] ?? $metadata['organization'] ?? null,
            'location' => $metadata['location'] ?? null,
            'estimated_value' => $this->estimateValue($item),
        ];
    }

    /**
     * Generate lead title
     */
    protected function generateLeadTitle(ScrapedItem $item): string
    {
        $metadata = $item->metadata ?? [];

        // For tenders, use tender-specific format
        if ($item->intent === 'tender') {
            $org = $metadata['organization'] ?? 'Organization';
            $tenderNum = $metadata['tender_number'] ?? '';
            
            if ($tenderNum) {
                return "Tender: {$org} - {$tenderNum}";
            }
            
            return "Tender: {$org}";
        }

        // For webhook/transactional, use contact/company if available
        if ($item->intent === 'transactional') {
            $company = $metadata['company'] ?? null;
            $name = $metadata['contact_name'] ?? null;
            
            if ($company) {
                return "Lead: {$company}";
            }
            
            if ($name) {
                return "Lead: {$name}";
            }
        }

        // Default: use item title truncated
        return mb_strlen($item->title) > 100 
            ? mb_substr($item->title, 0, 97) . '...' 
            : $item->title;
    }

    /**
     * Generate lead description
     */
    protected function generateLeadDescription(ScrapedItem $item): ?string
    {
        $parts = [];

        if ($item->description) {
            $parts[] = $item->description;
        }

        // Add source context
        $parts[] = "Source: {$item->source_name}";

        // Add URL if available
        if ($item->url) {
            $parts[] = "URL: {$item->url}";
        }

        // Add key metadata
        $metadata = $item->metadata ?? [];
        
        if (!empty($metadata['tender_number'])) {
            $parts[] = "Tender Number: {$metadata['tender_number']}";
        }

        if (!empty($metadata['closing_date'])) {
            $parts[] = "Closing Date: {$metadata['closing_date']}";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Find or create contact from item
     */
    protected function findOrCreateContact(ScrapedItem $item, array $leadData): ?Contact
    {
        // No contact info available
        if (empty($leadData['email']) && empty($leadData['phone'])) {
            return null;
        }

        // Try to find existing contact
        $query = Contact::where('organization_id', $item->organization_id);

        if (!empty($leadData['email'])) {
            $query->where('email', $leadData['email']);
        } elseif (!empty($leadData['phone'])) {
            $query->where('phone', $leadData['phone']);
        }

        $contact = $query->first();

        if ($contact) {
            // Update contact with any new information
            $this->updateContact($contact, $leadData);
            return $contact;
        }

        // Don't create contact yet - will be created after lead
        // The Lead model has hasOne Contact relationship
        return null;
    }

    /**
     * Update existing contact with new data
     */
    protected function updateContact(Contact $contact, array $leadData): void
    {
        $updates = [];

        // Update if we have better data
        if (empty($contact->company) && !empty($leadData['company'])) {
            $updates['company'] = $leadData['company'];
        }

        if (!empty($updates)) {
            $contact->update($updates);
        }
    }

    /**
     * Determine lead source
     */
    protected function determineSource(ScrapedItem $item): string
    {
        $sourceType = $item->sourceScraper->type ?? 'unknown';

        return match ($sourceType) {
            'webhook' => 'website',
            'tender' => 'tender_portal',
            'rss' => 'news_feed',
            default => $sourceType,
        };
    }

    /**
     * Determine lead quality
     */
    protected function determineQuality(ScrapedItem $item): string
    {
        $avgScore = ($item->opportunity_score + $item->lead_score) / 2;

        if ($avgScore >= 80) {
            return 'hot';
        } elseif ($avgScore >= 65) {
            return 'warm';
        } elseif ($avgScore >= 45) {
            return 'potential';
        } else {
            return 'low';
        }
    }

    /**
     * Estimate lead value
     */
    protected function estimateValue(ScrapedItem $item): ?float
    {
        $metadata = $item->metadata ?? [];

        // For tenders, use tender value
        if (!empty($metadata['value']['amount'])) {
            return (float) $metadata['value']['amount'];
        }

        // Estimate based on scores and intent
        if ($item->intent === 'tender') {
            // Tenders without explicit value - estimate conservatively
            if ($item->opportunity_score >= 80) {
                return 5000000; // 5M KES
            } elseif ($item->opportunity_score >= 60) {
                return 2000000; // 2M KES
            }
        }

        return null;
    }

    /**
     * Generate score explanation
     */
    protected function generateScoreExplanation(ScrapedItem $item): ?string
    {
        $explanations = [];

        if ($item->opportunity_score >= 60) {
            $explanations[] = "High opportunity score ({$item->opportunity_score}/100)";
        }

        if ($item->lead_score >= 60) {
            $explanations[] = "Strong lead quality ({$item->lead_score}/100)";
        }

        if ($item->relevance_score >= 70) {
            $explanations[] = "Highly relevant to tracked keywords";
        }

        if ($item->intent === 'transactional') {
            $explanations[] = "Ready to purchase - transactional intent";
        }

        if ($item->intent === 'tender') {
            $explanations[] = "Government tender opportunity";
        }

        $metadata = $item->metadata ?? [];
        if (!empty($metadata['urgency']) && $metadata['urgency'] === 'critical') {
            $explanations[] = "Critical urgency - closing soon";
        }

        return implode('. ', $explanations);
    }

    /**
     * Build lead metadata
     */
    protected function buildLeadMetadata(ScrapedItem $item): array
    {
        $metadata = [
            'scraped_item_id' => $item->id,
            'source_type' => $item->sourceScraper->type ?? null,
            'source_name' => $item->source_name,
            'source_url' => $item->url,
            'intent' => $item->intent,
            'opportunity_score' => $item->opportunity_score,
            'lead_score' => $item->lead_score,
            'relevance_score' => $item->relevance_score,
            'keywords_matched' => $item->matched_keywords,
            'auto_converted' => true,
        ];

        // Include tender-specific metadata
        if ($item->intent === 'tender') {
            $tenderData = $item->metadata ?? [];
            $metadata['tender'] = [
                'tender_number' => $tenderData['tender_number'] ?? null,
                'organization' => $tenderData['organization'] ?? null,
                'category' => $tenderData['category'] ?? null,
                'closing_date' => $tenderData['closing_date'] ?? null,
                'value' => $tenderData['value'] ?? null,
                'urgency' => $tenderData['urgency'] ?? null,
            ];
        }

        return $metadata;
    }

    /**
     * Batch convert eligible items to leads
     */
    public function batchConvert(int $organizationId, int $limit = 50): array
    {
        $items = ScrapedItem::where('organization_id', $organizationId)
            ->whereNull('lead_id')
            ->whereIn('processing_status', ['matched', 'processed'])
            ->where(function ($q) {
                // High-value items
                $q->where('opportunity_score', '>=', $this->conversionThresholds['opportunity_score'])
                  ->orWhere('lead_score', '>=', $this->conversionThresholds['lead_score'])
                  ->orWhereIn('intent', ['transactional', 'tender']);
            })
            ->orderByDesc('lead_score')
            ->orderByDesc('opportunity_score')
            ->limit($limit)
            ->get();

        $results = [
            'processed' => 0,
            'converted' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($items as $item) {
            $results['processed']++;
            
            try {
                $lead = $this->convertToLead($item);
                
                if ($lead) {
                    $results['converted']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                Log::error("Batch conversion failed for item", [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get conversion statistics
     */
    public function getConversionStats(int $organizationId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $stats = [
            'total_items' => ScrapedItem::where('organization_id', $organizationId)
                ->where('created_at', '>=', $since)
                ->count(),
            'converted_items' => ScrapedItem::where('organization_id', $organizationId)
                ->where('created_at', '>=', $since)
                ->whereNotNull('lead_id')
                ->count(),
            'pending_conversion' => ScrapedItem::where('organization_id', $organizationId)
                ->whereNull('lead_id')
                ->whereIn('processing_status', ['matched', 'processed'])
                ->where(function ($q) {
                    $q->where('opportunity_score', '>=', $this->conversionThresholds['opportunity_score'])
                      ->orWhere('lead_score', '>=', $this->conversionThresholds['lead_score']);
                })
                ->count(),
            'conversion_by_intent' => [],
            'conversion_by_source' => [],
        ];

        // Calculate conversion rate
        $stats['conversion_rate'] = $stats['total_items'] > 0
            ? round(($stats['converted_items'] / $stats['total_items']) * 100, 2)
            : 0;

        // Breakdown by intent
        $byIntent = ScrapedItem::where('organization_id', $organizationId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('lead_id')
            ->select('intent', DB::raw('COUNT(*) as count'))
            ->groupBy('intent')
            ->get();

        foreach ($byIntent as $row) {
            $stats['conversion_by_intent'][$row->intent] = $row->count;
        }

        return $stats;
    }

    /**
     * Set conversion thresholds
     */
    public function setThresholds(array $thresholds): void
    {
        $this->conversionThresholds = array_merge($this->conversionThresholds, $thresholds);
    }
}

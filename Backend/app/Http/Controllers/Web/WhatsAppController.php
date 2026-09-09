<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\Lead;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Show WhatsApp integration dashboard
     */
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Get WhatsApp data source
        $dataSource = DataSource::where('organization_id', $organizationId)
            ->where('provider', 'whatsapp')
            ->first();

        // Get WhatsApp leads
        $leads = Lead::where('organization_id', $organizationId)
            ->where('source', 'whatsapp')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate stats
        $stats = [
            'total_conversations' => Lead::where('organization_id', $organizationId)
                ->where('source', 'whatsapp')
                ->count(),
            'active_today' => Lead::where('organization_id', $organizationId)
                ->where('source', 'whatsapp')
                ->whereDate('last_contact_at', today())
                ->count(),
            'qualified_leads' => Lead::where('organization_id', $organizationId)
                ->where('source', 'whatsapp')
                ->where('status', 'qualified')
                ->count(),
            'response_rate' => 85, // Placeholder - calculate from actual data
        ];

        $isConfigured = $dataSource && $dataSource->status === 'active';

        return view('integrations.whatsapp.index', compact(
            'dataSource',
            'leads',
            'stats',
            'isConfigured'
        ));
    }

    /**
     * Show configuration form
     */
    public function configure(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $dataSource = DataSource::where('organization_id', $organizationId)
            ->where('provider', 'whatsapp')
            ->first();

        $webhookUrl = route('whatsapp.webhook');

        return view('integrations.whatsapp.configure', compact('dataSource', 'webhookUrl'));
    }

    /**
     * Save configuration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'access_token' => 'required|string',
            'phone_number_id' => 'required|string',
            'verify_token' => 'required|string',
            'business_account_id' => 'nullable|string',
        ]);

        $organizationId = $request->user()->organization_id;

        // Encrypt sensitive data
        $credentials = [
            'access_token' => $validated['access_token'],
            'phone_number_id' => $validated['phone_number_id'],
            'verify_token' => $validated['verify_token'],
            'business_account_id' => $validated['business_account_id'] ?? null,
        ];

        // Update or create data source
        DataSource::updateOrCreate(
            [
                'organization_id' => $organizationId,
                'provider' => 'whatsapp',
            ],
            [
                'name' => 'WhatsApp Business',
                'credentials' => Crypt::encryptString(json_encode($credentials)),
                'status' => 'active',
                'last_sync_at' => now(),
            ]
        );

        return redirect()
            ->route('whatsapp.index')
            ->with('success', 'WhatsApp integration configured successfully!');
    }

    /**
     * Test connection
     */
    public function test(Request $request)
    {
        $profile = $this->whatsappService->getBusinessProfile();

        if ($profile['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Connection successful!',
                'data' => $profile['data'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Connection failed: ' . ($profile['error'] ?? 'Unknown error'),
        ], 400);
    }

    /**
     * Send test message
     */
    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:1000',
        ]);

        $result = $this->whatsappService->sendMessage(
            $validated['phone'],
            $validated['message']
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Test message sent successfully!',
                'message_id' => $result['message_id'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send message: ' . ($result['error'] ?? 'Unknown error'),
        ], 400);
    }

    /**
     * View conversation with a lead
     */
    public function conversation(Request $request, Lead $lead)
    {
        // Ensure lead belongs to user's organization
        if ($lead->organization_id !== $request->user()->organization_id) {
            abort(403);
        }

        return view('integrations.whatsapp.conversation', compact('lead'));
    }

    /**
     * Send message to lead
     */
    public function sendMessage(Request $request, Lead $lead)
    {
        // Ensure lead belongs to user's organization
        if ($lead->organization_id !== $request->user()->organization_id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $result = $this->whatsappService->sendMessage(
            $lead->phone,
            $validated['message']
        );

        if ($result['success']) {
            // Log the sent message
            $lead->update([
                'message' => ($lead->message ?? '') . "\n[Sent " . now() . "]: {$validated['message']}",
                'last_contact_at' => now(),
            ]);

            return back()->with('success', 'Message sent successfully!');
        }

        return back()->with('error', 'Failed to send message: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Disconnect integration
     */
    public function disconnect(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        DataSource::where('organization_id', $organizationId)
            ->where('provider', 'whatsapp')
            ->update(['status' => 'inactive']);

        return redirect()
            ->route('whatsapp.index')
            ->with('success', 'WhatsApp integration disconnected.');
    }

    /**
     * Show statistics
     */
    public function statistics(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Get daily message counts for last 30 days
        $dailyMessages = Lead::where('organization_id', $organizationId)
            ->where('source', 'whatsapp')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Lead quality distribution
        $qualityDistribution = Lead::where('organization_id', $organizationId)
            ->where('source', 'whatsapp')
            ->selectRaw('quality, COUNT(*) as count')
            ->groupBy('quality')
            ->get();

        // Status distribution
        $statusDistribution = Lead::where('organization_id', $organizationId)
            ->where('source', 'whatsapp')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return view('integrations.whatsapp.statistics', compact(
            'dailyMessages',
            'qualityDistribution',
            'statusDistribution'
        ));
    }
}

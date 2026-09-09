<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index(Request $request)
    {
        $query = EmailTemplate::forAuth()
            ->with('creator')
            ->latest();

        // Filter by category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate(15);

        return view('campaigns.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create()
    {
        return view('campaigns.templates.create');
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:welcome,nurture,promotion,opportunity,follow_up,custom',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['created_by'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);

        $template = EmailTemplate::create($validated);

        return redirect()
            ->route('campaigns.templates.show', $template)
            ->with('success', 'Email template created successfully.');
    }

    /**
     * Display the specified template.
     */
    public function show(EmailTemplate $template)
    {
        $this->authorize('view', $template);

        $template->load('creator', 'campaigns');

        return view('campaigns.templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(EmailTemplate $template)
    {
        $this->authorize('update', $template);

        if ($template->is_system) {
            return redirect()
                ->route('campaigns.templates.show', $template)
                ->with('error', 'System templates cannot be edited. Please duplicate this template instead.');
        }

        return view('campaigns.templates.edit', compact('template'));
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, EmailTemplate $template)
    {
        $this->authorize('update', $template);

        if ($template->is_system) {
            return redirect()
                ->route('campaigns.templates.show', $template)
                ->with('error', 'System templates cannot be edited.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:welcome,nurture,promotion,opportunity,follow_up,custom',
            'subject' => 'required|string|max:255',
            'preview_text' => 'nullable|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $template->update($validated);

        return redirect()
            ->route('campaigns.templates.show', $template)
            ->with('success', 'Email template updated successfully.');
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(EmailTemplate $template)
    {
        $this->authorize('view', $template);

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->is_system = false;
        $newTemplate->created_by = Auth::id();
        $newTemplate->usage_count = 0;
        $newTemplate->last_used_at = null;
        $newTemplate->save();

        return redirect()
            ->route('campaigns.templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully.');
    }

    /**
     * Preview a template.
     */
    public function preview(EmailTemplate $template, Request $request)
    {
        $this->authorize('view', $template);

        // Get sample data for preview
        $sampleData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Acme Corp',
            'email' => 'john@example.com',
            'opportunity_title' => 'Growing Demand for Widgets',
            'growth_percentage' => '45%',
        ];

        // Replace variables
        $previewContent = $template->replaceVariables($sampleData);

        return view('campaigns.templates.preview', [
            'template' => $template,
            'preview' => $previewContent,
        ]);
    }

    /**
     * Remove the specified template.
     */
    public function destroy(EmailTemplate $template)
    {
        $this->authorize('delete', $template);

        if ($template->is_system) {
            return redirect()
                ->route('campaigns.templates.index')
                ->with('error', 'System templates cannot be deleted.');
        }

        $template->delete();

        return redirect()
            ->route('campaigns.templates.index')
            ->with('success', 'Email template deleted successfully.');
    }
}

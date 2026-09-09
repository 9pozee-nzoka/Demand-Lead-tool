<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Display all tasks
     */
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Filter parameters
        $filter = $request->get('filter', 'all'); // all, my, overdue, today
        $status = $request->get('status');

        $query = Task::where('organization_id', $organizationId)
            ->with(['lead', 'deal', 'assignedUser']);

        // Apply filters
        if ($filter === 'my') {
            $query->where('assigned_user_id', $request->user()->id);
        } elseif ($filter === 'overdue') {
            $query->overdue();
        } elseif ($filter === 'today') {
            $query->whereDate('due_at', today());
        }

        if ($status) {
            $query->where('status', $status);
        }

        $tasks = $query->orderBy('due_at', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate stats
        $stats = [
            'total' => Task::where('organization_id', $organizationId)->count(),
            'pending' => Task::where('organization_id', $organizationId)
                ->where('status', 'pending')
                ->count(),
            'overdue' => Task::where('organization_id', $organizationId)
                ->overdue()
                ->count(),
            'completed_today' => Task::where('organization_id', $organizationId)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
        ];

        // Get team members for assignment
        $teamMembers = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return view('tasks.index', compact('tasks', 'stats', 'teamMembers', 'filter'));
    }

    /**
     * Show task creation form
     */
    public function create(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $leads = Lead::where('organization_id', $organizationId)
            ->whereIn('status', ['new', 'qualified', 'contacted'])
            ->get();

        $deals = Deal::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->get();

        $teamMembers = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return view('tasks.create', compact('leads', 'deals', 'teamMembers'));
    }

    /**
     * Store new task
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:call,email,meeting,follow_up,research,other',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'lead_id' => 'nullable|exists:leads,id',
            'deal_id' => 'nullable|exists:deals,id',
            'assigned_user_id' => 'required|exists:users,id',
            'due_at' => 'required|date',
        ]);

        $validated['organization_id'] = $request->user()->organization_id;
        $validated['status'] = $validated['status'] ?? 'pending';

        Task::create($validated);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task created successfully!');
    }

    /**
     * Show single task
     */
    public function show(Task $task)
    {
        $task->load(['lead', 'deal', 'assignedUser']);

        return view('tasks.show', compact('task'));
    }

    /**
     * Show edit form
     */
    public function edit(Task $task)
    {
        $organizationId = auth()->user()->organization_id;

        $leads = Lead::where('organization_id', $organizationId)->get();
        $deals = Deal::where('organization_id', $organizationId)->get();
        $teamMembers = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return view('tasks.edit', compact('task', 'leads', 'deals', 'teamMembers'));
    }

    /**
     * Update task
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:call,email,meeting,follow_up,research,other',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'lead_id' => 'nullable|exists:leads,id',
            'deal_id' => 'nullable|exists:deals,id',
            'assigned_user_id' => 'required|exists:users,id',
            'due_at' => 'required|date',
        ]);

        // Set completed_at if status changed to completed
        if ($validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $task->update($validated);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task updated successfully!');
    }

    /**
     * Delete task
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    /**
     * Mark task as completed
     */
    public function complete(Task $task)
    {
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task marked as completed',
                'task' => $task->load(['lead', 'deal', 'assignedUser']),
            ]);
        }

        return back()->with('success', 'Task completed!');
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        if ($validated['status'] === 'completed') {
            $task->completed_at = now();
        } else {
            $task->completed_at = null;
        }

        $task->status = $validated['status'];
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Task status updated',
            'task' => $task->load(['lead', 'deal', 'assignedUser']),
        ]);
    }

    /**
     * My tasks (quick view for current user)
     */
    public function myTasks(Request $request)
    {
        $userId = $request->user()->id;
        $organizationId = $request->user()->organization_id;

        $tasks = Task::where('organization_id', $organizationId)
            ->where('assigned_user_id', $userId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['lead', 'deal'])
            ->orderBy('due_at', 'asc')
            ->get();

        // Group by status
        $tasksByStatus = [
            'overdue' => $tasks->filter(fn($t) => $t->due_at && $t->due_at->isPast())->values(),
            'today' => $tasks->filter(fn($t) => $t->due_at && $t->due_at->isToday())->values(),
            'upcoming' => $tasks->filter(fn($t) => $t->due_at && $t->due_at->isFuture() && !$t->due_at->isToday())->values(),
        ];

        return view('tasks.my-tasks', compact('tasksByStatus'));
    }

    /**
     * Calendar view of tasks
     */
    public function calendar(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $tasks = Task::where('organization_id', $organizationId)
            ->whereNotNull('due_at')
            ->with(['lead', 'deal', 'assignedUser'])
            ->get();

        // Format for calendar
        $events = $tasks->map(function ($task) {
            $color = match($task->status) {
                'completed' => '#28a745',
                'in_progress' => '#ffc107',
                'cancelled' => '#dc3545',
                default => '#007bff',
            };

            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->due_at->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'url' => route('tasks.show', $task),
            ];
        });

        return view('tasks.calendar', compact('events'));
    }

    /**
     * Activity timeline for lead or deal
     */
    public function timeline(Request $request)
    {
        $organizationId = $request->user()->organization_id;
        $leadId = $request->get('lead_id');
        $dealId = $request->get('deal_id');

        $query = Task::where('organization_id', $organizationId)
            ->with(['assignedUser']);

        if ($leadId) {
            $query->where('lead_id', $leadId);
        }

        if ($dealId) {
            $query->where('deal_id', $dealId);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return view('tasks.timeline', compact('tasks', 'leadId', 'dealId'));
    }
}

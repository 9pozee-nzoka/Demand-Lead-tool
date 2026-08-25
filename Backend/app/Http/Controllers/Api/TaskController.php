<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::where('organization_id', $request->user()->organization_id)
            ->with(['assignedUser:id,name'])
            ->when($request->filled('lead_id'), fn ($q) => $q->where('lead_id', $request->lead_id))
            ->when($request->filled('deal_id'), fn ($q) => $q->where('deal_id', $request->deal_id))
            ->when($request->filled('status'),  fn ($q) => $q->where('status', $request->status))
            ->orderBy('due_at')
            ->paginate(25);

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id'          => ['nullable', 'integer', 'exists:leads,id'],
            'deal_id'          => ['nullable', 'integer', 'exists:deals,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'type'             => ['nullable', Rule::in(['call','email','meeting','follow_up','other'])],
            'due_at'           => ['nullable', 'date'],
        ]);

        $task = Task::create([
            'organization_id' => $request->user()->organization_id,
            'status'          => 'pending',
            ...$data,
        ]);

        return response()->json($task->load('assignedUser'), 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);

        $data = $request->validate([
            'title'  => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['pending','in_progress','completed','cancelled'])],
            'due_at' => ['nullable', 'date'],
            'type'   => ['sometimes', Rule::in(['call','email','meeting','follow_up','other'])],
        ]);

        if (($data['status'] ?? null) === 'completed' && ! $task->completed_at) {
            $data['completed_at'] = now();
        }

        $task->update($data);
        return response()->json($task->fresh());
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);
        $task->delete();
        return response()->json(['message' => 'Task deleted.']);
    }

    private function authorizeTask(Request $request, Task $task): void
    {
        if ((int) $task->organization_id !== (int) $request->user()->organization_id) abort(403);
    }
}

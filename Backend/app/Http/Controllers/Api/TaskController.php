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
        $query = Task::where('organization_id', $request->user()->organization_id)
            ->with(['lead', 'deal', 'assignedUser']);

        if ($request->filled('assigned_to')) {
            $query->where('assigned_user_id', $request->assigned_to);
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        return response()->json($query->orderBy('due_at')->paginate(25));
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
            ...$data,
        ]);

        return response()->json($task, 201);
    }
}

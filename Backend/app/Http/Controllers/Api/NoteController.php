<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notes = Note::where('organization_id', $request->user()->organization_id)
            ->with(['user:id,name'])
            ->when($request->filled('lead_id'), fn ($q) => $q->where('lead_id', $request->lead_id))
            ->when($request->filled('deal_id'), fn ($q) => $q->where('deal_id', $request->deal_id))
            ->latest()
            ->paginate(25);

        return response()->json($notes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'deal_id' => ['nullable', 'integer', 'exists:deals,id'],
            'body'    => ['required', 'string', 'max:5000'],
        ]);

        $note = Note::create([
            'organization_id' => $request->user()->organization_id,
            'user_id'         => $request->user()->id,
            ...$data,
        ]);

        return response()->json($note->load('user'), 201);
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $this->authorizeNote($request, $note);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $note->update($data);
        return response()->json($note->fresh());
    }

    public function destroy(Request $request, Note $note): JsonResponse
    {
        $this->authorizeNote($request, $note);
        $note->delete();
        return response()->json(['message' => 'Note deleted.']);
    }

    private function authorizeNote(Request $request, Note $note): void
    {
        if ((int) $note->organization_id !== (int) $request->user()->organization_id) abort(403);
    }
}

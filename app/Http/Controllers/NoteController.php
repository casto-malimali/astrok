<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Services\SyncNoteTags;
use Illuminate\Http\Request;
use App\Http\Resources\NoteResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;

//For Email
use Illuminate\Support\Facades\Mail;
use App\Mail\NoteCreatedMail;

class NoteController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $r)
    {
        $limit = min($r->integer('limit', 25), 100);
        $query = trim((string) $r->query('query', ''));
        $tag = trim((string) $r->query('tag', ''));
        $from = $r->date('from');
        $to = $r->date('to');
        $sort = (string) $r->query('sort', '-created_at');

        $notes = $r->user()->notes()
            ->with('tags')
            ->when($query !== '', fn($x) => $x->where(fn($y) =>
                $y->where('title', 'like', "%$query%")->orWhere('body', 'like', "%$query%")))
            ->when($tag !== '', fn($x) => $x->whereHas('tags', fn($t) =>
                $t->where('name', mb_strtolower($tag))))
            ->when($from, fn($x) => $x->whereDate('created_at', '>=', $from))
            ->when($to, fn($x) => $x->whereDate('created_at', '<=', $to))
            ->when($sort, function ($x) use ($sort) {
                $dir = str_starts_with($sort, '-') ? 'desc' : 'asc';
                $col = ltrim($sort, '-');
                return $x->orderBy($col, $dir);
            }, fn($x) => $x->latest())
            ->paginate($limit);
        return NoteResource::collection($notes->load('attachments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNoteRequest $request)
    {

        $note = Auth::user()->notes()->create($request->validated());
        SyncNoteTags::handle($note, $request->validated()['tags'] ?? []);

        Mail::to(Auth::user()->email)->queue(new NoteCreatedMail($note));
        return (new NoteResource($note))->response()->setStatusCode(201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note)
    {

        $this->authorize('view', $note);
        return new NoteResource($note->load('tags'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Note $note)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        $this->authorize('update', $note);

        $note->update($request->validated());
        SyncNoteTags::handle($note, $request->validated()['tags'] ?? []);
        return new NoteResource($note->load('tags', 'attachments'));
    }


    public function restore($id, Request $r)
    {
        $note = Note::onlyTrashed()->findOrFail($id);
        $this->authorize('update', $note);
        // owner check via policy still applies if you add it to handle trashed (optional)
        $note->restore();
        return new NoteResource($note->fresh('tags'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);
        $note->delete();
        return response()->noContent();
    }
}

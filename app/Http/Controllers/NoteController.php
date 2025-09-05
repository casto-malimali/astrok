<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use App\Http\Resources\NoteResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;

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
        $note = $r->user()->notes()
            ->when($query !== '', fn($x) => $x->where(fn($y) => $y->where('title', 'like', "%$query%")))
            ->latest()
            ->paginate($limit);
        return NoteResource::collection($note);
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

        return (new NoteResource($note))->response()->setStatusCode(201);


    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note)
    {
        $this->authorize('view', $note);
        return new NoteResource($note);
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
        return new NoteResource($note);
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

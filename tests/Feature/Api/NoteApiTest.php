<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Note;

uses(RefreshDatabase::class);

it('requires auth', function () {
    $this->getJson('/api/notes')->assertStatus(401);
});

it('lists only my notes', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    Note::factory()->for($me)->create(['title' => 'Mine']);
    Note::factory()->for($other)->create(['title' => 'NotMine']);

    // Authenticate via Sanctum
    Sanctum::actingAs($me);

    $this->getJson('/api/notes')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Mine'])
        ->assertJsonMissing(['title' => 'NotMine']);
});

it('creates, shows, updates, deletes a note', function () {
    $me = User::factory()->create();
    Sanctum::actingAs($me);

    // If your controller returns a Resource (recommended), response shape is { data: {...} }
    $created = $this->postJson('/api/notes', ['title' => 'A', 'body' => 'B'])
        ->assertCreated()
        ->json('data');

    $id = $created['id'];

    $this->getJson("/api/notes/{$id}")
        ->assertOk()
        ->assertJsonFragment(['title' => 'A']);

    $this->putJson("/api/notes/{$id}", ['title' => 'A2', 'body' => 'B2'])
        ->assertOk()
        ->assertJsonFragment(['title' => 'A2']);

    $this->deleteJson("/api/notes/{$id}")
        ->assertNoContent();
});

it('blocks IDOR via policy', function () {
    [$u1, $u2] = User::factory()->count(2)->create();
    $note = Note::factory()->for($u1)->create();

    Sanctum::actingAs($u2);

    $this->getJson("/api/notes/{$note->id}")
        ->assertStatus(403);
});

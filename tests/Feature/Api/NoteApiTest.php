<?php

use App\Models\Note;
use App\Models\User;
use App\Models\Attachment;
use Laravel\Sanctum\Sanctum;
use App\Mail\NoteCreatedMail;
use App\Services\SyncNoteTags;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GenerateAttachmentThumbnail;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

it('filters by tag and q and sorts', function () {
    $me = \App\Models\User::factory()->create();
    \Laravel\Sanctum\Sanctum::actingAs($me);

    $a = $me->notes()->create(['title' => 'Alpha note', 'body' => 'hello world']);
    $b = $me->notes()->create(['title' => 'Beta note', 'body' => 'another hello']);
    \App\Services\SyncNoteTags::handle($a, ['work']);
    \App\Services\SyncNoteTags::handle($b, ['home']);

    $this->getJson('/api/notes?tag=work&q=hello&sort=title')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Alpha note'])
        ->assertJsonMissing(['title' => 'Beta note']);
});

it('soft deletes and restores', function () {
    $me = \App\Models\User::factory()->create();
    \Laravel\Sanctum\Sanctum::actingAs($me);

    $n = $me->notes()->create(['title' => 'To delete', 'body' => 'b']);
    $this->deleteJson("/api/notes/{$n->id}")->assertNoContent();

    // should be gone from normal list
    $this->getJson('/api/notes')->assertOk()->assertJsonMissing(['title' => 'To delete']);

    // restore
    $this->postJson("/api/notes/{$n->id}/restore")->assertOk()
        ->assertJsonFragment(['title' => 'To delete']);
});

it('uploads an attachment privately and queues a thumbnail', function () {
    Storage::fake('private');
    Queue::fake();

    $me = \App\Models\User::factory()->create();
    Sanctum::actingAs($me);

    $note = $me->notes()->create(['title' => 'Has file', 'body' => '...']);

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
    $res = $this->postJson("/api/notes/{$note->id}/attachments", ['file' => $file]);

    $res->assertCreated()->assertJsonStructure(['id', 'download_url']);
    $attId = $res->json('id');

    // File stored
    $att = \App\Models\Attachment::find($attId);
    $this->assertTrue(Storage::disk('private')->exists($att->path));

    // Job queued
    Queue::assertPushed(GenerateAttachmentThumbnail::class, fn($job) => $job->attachment->id === $attId);
});

it('sends an email on note creation', function () {
    Mail::fake();

    $me = \App\Models\User::factory()->create();
    Sanctum::actingAs($me);

    $this->postJson('/api/notes', ['title' => 'Mail me', 'body' => 'test'])->assertCreated();

    Mail::assertQueued(NoteCreatedMail::class, function ($m) use ($me) {
        return $m->hasTo($me->email);
    });
});


it('forbids write when token lacks ability', function () {
    $me = User::factory()->create();
    Sanctum::actingAs($me, ['notes:read']); // read-only

    $this->postJson('/api/v1/notes', ['title' => 'X', 'body' => 'Y'])
        ->assertStatus(403); // blocked by abilities:notes:write
});

it('allows read with read-only ability', function () {
    $me = User::factory()->create();
    $me->notes()->create(['title' => 'A', 'body' => 'B']);

    Sanctum::actingAs($me, ['notes:read']); // read-only
    $this->getJson('/api/v1/notes')->assertOk()
        ->assertJsonFragment(['title' => 'A']);
});

it('admin route blocked for non-admin', function () {
    $me = User::factory()->create(['is_admin' => false]);
    Sanctum::actingAs($me, ['notes:read', 'notes:write']);

    $this->getJson('/api/v1/admin/users')->assertStatus(403);
});

it('admin route allowed for admin with admin ability', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin, ['admin', 'notes:read', 'notes:write']);

    $this->getJson('/api/v1/admin/users')->assertOk();
});

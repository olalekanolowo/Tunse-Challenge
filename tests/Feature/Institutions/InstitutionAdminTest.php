<?php

use App\Models\Institution;
use App\Models\User;

it('lets a guest list only active institutions for the registration dropdown', function () {
    Institution::factory()->create(['name' => 'Active U', 'active' => true]);
    Institution::factory()->create(['name' => 'Inactive U', 'active' => false]);

    $response = $this->getJson('/api/institutions');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('name'))->toEqual(collect(['Active U']));
});

it('forbids a student from creating an institution', function () {
    $student = User::factory()->create();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/admin/institutions', ['name' => 'X', 'short_code' => 'X', 'state' => 'Lagos'])
        ->assertForbidden();
});

it('forbids an auditor from creating an institution but allows read access', function () {
    $auditor = User::factory()->auditor()->create();

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson('/api/admin/institutions', ['name' => 'X', 'short_code' => 'X', 'state' => 'Lagos'])
        ->assertForbidden();

    $this->withHeaders(apiAuthHeader($auditor))
        ->getJson('/api/admin/institutions')
        ->assertOk();
});

it('allows an admin to create and update an institution', function () {
    $admin = User::factory()->admin()->create();

    $created = $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/institutions', [
            'name' => 'New University',
            'short_code' => 'NEWU',
            'state' => 'Lagos',
        ])->assertCreated()->json('data');

    $this->withHeaders(apiAuthHeader($admin))
        ->patchJson("/api/admin/institutions/{$created['id']}", ['active' => false])
        ->assertOk()
        ->assertJsonPath('data.active', false);
});

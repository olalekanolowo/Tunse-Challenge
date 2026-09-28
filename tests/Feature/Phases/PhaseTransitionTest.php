<?php

use App\Models\Phase;
use App\Models\User;

it('allows an admin to transition a phase through the legal order', function () {
    $admin = User::factory()->admin()->create();
    $phase = Phase::factory()->create(['status' => 'draft']);

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson("/api/admin/phases/{$phase->id}/transition", ['status' => 'open'])
        ->assertOk()->assertJsonPath('data.status', 'open');

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson("/api/admin/phases/{$phase->id}/transition", ['status' => 'frozen'])
        ->assertOk()->assertJsonPath('data.status', 'frozen');

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson("/api/admin/phases/{$phase->id}/transition", ['status' => 'closed'])
        ->assertOk()->assertJsonPath('data.status', 'closed');
});

it('rejects an illegal phase transition', function () {
    $admin = User::factory()->admin()->create();
    $phase = Phase::factory()->create(['status' => 'draft']);

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson("/api/admin/phases/{$phase->id}/transition", ['status' => 'closed'])
        ->assertUnprocessable();
});

it('only allows a super admin to reopen a frozen phase', function () {
    $admin = User::factory()->admin()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $phase = Phase::factory()->frozen()->create();

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson("/api/admin/phases/{$phase->id}/transition", ['status' => 'open'])
        ->assertUnprocessable();

    $this->withHeaders(apiAuthHeader($superAdmin))
        ->postJson("/api/admin/phases/{$phase->id}/transition", ['status' => 'open'])
        ->assertOk()->assertJsonPath('data.status', 'open');
});

it('forbids a non-admin from transitioning a phase', function () {
    $auditor = User::factory()->auditor()->create();
    $phase = Phase::factory()->create(['status' => 'draft']);

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson("/api/admin/phases/{$phase->id}/transition", ['status' => 'open'])
        ->assertForbidden();
});

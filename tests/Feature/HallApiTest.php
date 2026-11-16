<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HallApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_see_halls_list(): void
    {
        Hall::factory()->count(3)->create();

        $this->getJson('/api/v1/halls')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_create_hall(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/halls', ['name' => 'Банкетный зал'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Банкетный зал');

        $this->assertDatabaseHas('halls', ['name' => 'Банкетный зал']);
    }

    public function test_client_cannot_create_hall(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/halls', ['name' => 'Банкетный зал'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('halls', ['name' => 'Банкетный зал']);
    }

    public function test_hall_name_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        Hall::factory()->create(['name' => 'Бар']);

        $this->postJson('/api/v1/halls', ['name' => 'Бар'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_hall_with_tables_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $hall = Hall::factory()->create();
        Table::factory()->create(['hall_id' => $hall->id]);

        $this->deleteJson("/api/v1/halls/{$hall->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Нельзя удалить зал, в котором есть столики.');
    }

    public function test_missing_hall_returns_404(): void
    {
        $this->getJson('/api/v1/halls/999')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Зал не найден');
    }
}

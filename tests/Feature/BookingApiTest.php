<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Dish;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Table $table, array $override = []): array
    {
        $start = now()->addDays(3)->setTime(19, 0);

        return array_merge([
            'table_id' => $table->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->copy()->addHours(2)->format('Y-m-d H:i:s'),
            'guests_count' => 2,
        ], $override);
    }

    public function test_client_can_book_table_with_preorder(): void
    {
        $client = User::factory()->create();
        $table = Table::factory()->create(['seats' => 4]);
        $dish = Dish::factory()->create(['price' => 500]);
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/bookings', $this->payload($table, [
            'dishes' => [['dish_id' => $dish->id, 'quantity' => 2]],
        ]))->assertStatus(201)
            ->assertJsonPath('data.status', 'new');

        $this->assertEquals(1000, $response->json('data.preorder_total'));

        $this->assertDatabaseHas('bookings', ['user_id' => $client->id, 'table_id' => $table->id]);
        $this->assertDatabaseHas('booking_dish', ['dish_id' => $dish->id, 'quantity' => 2]);
    }

    public function test_cannot_book_busy_table(): void
    {
        $table = Table::factory()->create(['seats' => 4]);
        $start = now()->addDays(3)->setTime(19, 0);
        Booking::factory()->create([
            'table_id' => $table->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'status' => BookingStatus::Confirmed,
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/bookings', $this->payload($table, [
            'starts_at' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
            'ends_at' => $start->copy()->addHours(3)->format('Y-m-d H:i:s'),
        ]))->assertStatus(409)
            ->assertJsonPath('message', 'Столик уже занят на выбранное время.');
    }

    public function test_guests_cannot_exceed_table_seats(): void
    {
        $table = Table::factory()->create(['seats' => 2]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/bookings', $this->payload($table, ['guests_count' => 5]))
            ->assertStatus(409);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_unavailable_dish_rolls_back_booking(): void
    {
        $table = Table::factory()->create(['seats' => 4]);
        $dish = Dish::factory()->create(['is_available' => false]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/bookings', $this->payload($table, [
            'dishes' => [['dish_id' => $dish->id, 'quantity' => 1]],
        ]))->assertStatus(409);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_client_sees_only_own_bookings(): void
    {
        $client = User::factory()->create();
        Booking::factory()->count(2)->create(['user_id' => $client->id]);
        Booking::factory()->count(3)->create();
        Sanctum::actingAs($client);

        $this->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_client_cannot_view_foreign_booking(): void
    {
        $foreign = Booking::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/bookings/{$foreign->id}")
            ->assertStatus(403);
    }

    public function test_status_changes_only_by_allowed_transitions(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Cancelled]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/v1/bookings/{$booking->id}/status", ['status' => 'confirmed'])
            ->assertStatus(409);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_client_cannot_cancel_less_than_two_hours_before_start(): void
    {
        $client = User::factory()->create();
        $start = now()->addHour();
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'status' => BookingStatus::New,
        ]);
        Sanctum::actingAs($client);

        $this->postJson("/api/v1/bookings/{$booking->id}/cancel")
            ->assertStatus(409);
    }

    public function test_bookings_can_be_filtered_sorted_and_paginated(): void
    {
        Booking::factory()->count(4)->create(['status' => BookingStatus::Confirmed]);
        Booking::factory()->count(3)->create(['status' => BookingStatus::New]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/bookings?status=confirmed&sort=guests_count&order=asc&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/v1/bookings?sort=password')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort');
    }

    public function test_only_admin_gets_stats(): void
    {
        Booking::factory()->count(3)->create(['status' => BookingStatus::New]);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/stats')->assertStatus(403);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson('/api/v1/stats')
            ->assertOk()
            ->assertJsonPath('data.bookings_total', 3);
    }
}

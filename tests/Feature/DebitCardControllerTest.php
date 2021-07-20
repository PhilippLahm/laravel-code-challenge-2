<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    /**
     * @return mixed
     */
    public function createDebitCard()
    {
        return DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        DebitCard::factory()->count(3)->active()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get('api/debit-cards')
            ->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'number',
                    'type',
                    'expiration_date',
                    'is_active',
                ]
            ])
            ->assertJsonCount(3);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->get('api/debit-cards')
            ->assertStatus(200)
            ->assertJsonMissing([
                'id' => $debitCard->id,
            ]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $payload = ['type' => 'Visa Master Card'];
        $response = $this->post('api/debit-cards', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active'
            ])
            ->assertJson($payload)
            ->assertJsonMissingExact([
                'number' => null,
                'type' => 'Credit'
            ]);

    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = $this->createDebitCard();

        $this->get('api/debit-cards/' . $debitCard->id)
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ])
            ->assertJson([
                'id' => $debitCard->id,
                'number' => $debitCard->number,
                'type' => $debitCard->type,
                'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => true,
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->get('api/debit-cards/' . $debitCard->id)
            ->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->expired()->create([
            'user_id' => $this->user->id,
        ]);

        $this->put('api/debit-cards/' . $debitCard->id, ['is_active' => 1])
            ->assertStatus(200)
            ->assertJson([
                'id' => $debitCard->id,
                'number' => $debitCard->number,
                'type' => $debitCard->type,
                'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => true,
            ]);


    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = $this->createDebitCard();

        $this->put('api/debit-cards/' . $debitCard->id, ['is_active' => 0])
            ->assertStatus(200)
            ->assertJson([
                'id' => $debitCard->id,
                'number' => $debitCard->number,
                'type' => $debitCard->type,
                'expiration_date' => $debitCard->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => false,
            ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = $this->createDebitCard();

        $response = $this->json('PUT', 'api/debit-cards/' . $debitCard->id, ['is_active' => 'foo']);
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'is_active'
            ]);

    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = $this->createDebitCard();

        $this->json('DELETE', 'api/debit-cards/' . $debitCard->id)
            ->assertStatus(204);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('DELETE', 'api/debit-cards/' . $debitCard->id)
            ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}

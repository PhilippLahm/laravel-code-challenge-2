<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        $this->faker = Factory::create();
        Passport::actingAs($this->user);
    }

    private function createDebitCardTransaction(DebitCard $debitCard, $amount = null, $currentCode = null)
    {
        return $debitCard->debitCardTransactions()->create([
            'amount' => $amount ?? $this->faker->randomNumber(),
            'currency_code' => $currentCode ?? $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
        ]);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        $debitCardTransaction = $this->createDebitCardTransaction($this->debitCard);
        $this->json('GET', 'api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'amount',
                    'currency_code',
                ]
            ])
            ->assertJsonFragment([
                'amount' => (string)$debitCardTransaction->amount,
                'currency_code' => $debitCardTransaction->currency_code,
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherDebitCard = DebitCard::factory()->create();

        $this->json('GET', 'api/debit-card-transactions', [
            'debit_card_id' => $otherDebitCard->id,
        ])
            ->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $response = $this->post('api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 250,
            'currency_code' => DebitCardTransaction::CURRENCY_THB,
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ])
            ->assertJson([
                'amount' => 250,
                'currency_code' => DebitCardTransaction::CURRENCY_THB,
            ]);

    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherDebitCard = DebitCard::factory()->create();
        $this->post('api/debit-card-transactions', [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 250,
            'currency_code' => DebitCardTransaction::CURRENCY_THB,
        ])->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}

        $debitCardTransaction = $this->createDebitCardTransaction($this->debitCard);

        $this->get('api/debit-card-transactions/' . $debitCardTransaction->id)
            ->assertStatus(200)
            ->assertJsonStructure([
                'amount',
                'currency_code',
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherDebitCard = DebitCard::factory()->create();
        $debitCardTransaction = $this->createDebitCardTransaction($otherDebitCard);

        $this->get('api/debit-card-transactions/' . $debitCardTransaction->id)
            ->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}

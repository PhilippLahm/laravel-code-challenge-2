<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => fn() => User::factory()->create(),
            'amount' => $this->faker->randomNumber(),
            'terms' => $this->faker->randomNumber(6),
            'outstanding_amount' => $this->faker->randomNumber(),
            'currency_code' => $this->faker->randomElement([
                Loan::CURRENCY_VND, Loan::CURRENCY_SGD
            ]),
            'processed_at' => $this->faker->dateTimeBetween('+3 month', '+6 year'),
            'status' => $this->faker->randomElement([
                Loan::STATUS_DUE, Loan::STATUS_REPAID
            ]),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'loan_id' => fn() => Loan::factory()->create(),
            'amount' => $this->faker->randomNumber(),
            'outstanding_amount' => $this->faker->randomNumber(),
            'currency_code' => $this->faker->randomElement([
                Loan::CURRENCY_VND, Loan::CURRENCY_SGD
            ]),
            'due_date' => $this->faker->dateTimeBetween('+3 month', '+6 month'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store' => 'Albert Heijn',
            'purchased_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            // Don't set purchased_date here - let model's booted() hook derive it
            // from purchased_at. This ensures tests that override purchased_at
            // get the correct purchased_date automatically.
            'total_amount' => $this->faker->randomFloat(2, 5, 200),
            'pdf_path' => null,
            'raw_text' => $this->faker->text(200),
        ];
    }
}

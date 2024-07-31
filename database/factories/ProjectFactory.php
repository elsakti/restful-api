<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    private function generateUniqueCode()
    {
        $uuid = Uuid::uuid4();
        $code = substr($uuid->toString(), 0, 6);
        $code = Str::upper($code);
        return $code;
    }
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $startDate = $this->faker->dateTimeThisMonth();
        $dueDate = $this->faker->dateTimeBetween($startDate, '+1 month');

        return [
            'name' => $this->faker->sentence(2),
            'file' => $this->faker->sentence(1) . $this->faker->randomElement(['pdf', 'docs', 'png']),
            'description' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'code' => $this->generateUniqueCode(),
        ];
    }

    // public function pending()
    // {
    //     return $this->state(function (array $attributes) {
    //         return [
    //             'status' => 'pending',
    //         ];
    //     });
    // }

    // public function inProgress()
    // {
    //     return $this->state(function (array $attributes) {
    //         return [
    //             'status' => 'in_progress',
    //         ];
    //     });
    // }

    // public function completed()
    // {
    //     return $this->state(function (array $attributes) {
    //         return [
    //             'status' => 'completed',
    //         ];
    //     });
    // }


}

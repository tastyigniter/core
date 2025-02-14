<?php

declare(strict_types=1);

namespace Igniter\System\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;

class PageFactory extends Factory
{
    protected $model = \Igniter\System\Models\Page::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'permalink_slug' => $this->faker->slug,
            'content' => $this->faker->paragraph,
            'language_id' => 1,
            'meta_description' => $this->faker->paragraph(1),
            'meta_keywords' => $this->faker->words(3, true),
            'layout' => 'default',
            'status' => $this->faker->boolean(),
        ];
    }

    public function hidden(): self
    {
        return $this->state(function(array $attributes) {
            return [
                'metadata' => ['navigation_hidden' => true],
            ];
        });
    }
}

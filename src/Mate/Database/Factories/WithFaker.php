<?php
namespace Mate\Database\Factories;

trait WithFaker {
    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Inicializa una instancia de Faker.
     */
    protected function setupFaker($locale = 'en_US') {
        $this->faker = \Faker\Factory::create($locale);
    }
}
<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;

use App\Entity\FacturationModel;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FacturationModelFixtures extends Fixture
{

    public const PREFIX = "facturationModel#";
    public const POOL_MIN = 0;
    public const POOL_MAX = 10;

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();

        for ($i = self::POOL_MIN; $i < self::POOL_MAX; $i++) {
            $dateCreated = $this->faker->dateTimeInInterval('-1 year', '+1 year');
            $dateUpdated = $this->faker->dateTimeBetween($dateCreated, $now);

            $facturationModel = new facturationModel();

            $facturationModel
                ->setName($this->faker->numerify('facturation-model-###'))
                ->setCreatedAt($dateCreated)
                ->setUpdatedAt($dateUpdated)
                ->setStatus('on')
            ;
            // $account->setCreatedAt(new \DateTime());
            $manager->persist($facturationModel);
            $this->addReference(name: self::PREFIX . $i, object: $facturationModel);
        }
        $manager->flush();
    }
}

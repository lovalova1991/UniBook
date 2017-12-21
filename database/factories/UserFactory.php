<?php

use Faker\Generator as Faker;

#crea utente

$factory->define(App\User::class, function (Faker $faker) {
    return [
      'name' => $faker->firstName,
      'surname' => $faker->lastName,
      'birth_date' => $faker->date($format = 'Y-m-d', $max = 'now'),
      'email' => $faker->safeEmail,
      'roles'=> 0,
      'pwd_hash' => bcrypt('secret'),
      'gender' => $faker->randomElement(['0', '1']),
      'citta' => $faker->randomElement(['Ferrara','Bologna','Firenze','Roma','Milano','Venezia','Londra','Parigi','Berlino','Torino']),
      'ban' => 0,
      'id_user' => uniqid(),
      'pic_path' => $faker->randomElement(['assets/images/facebook1.jpg','assets/images/facebook2.jpg','assets/images/facebook3.jpeg','assets/images/facebook4.jpeg','assets/images/facebook5.jpeg','assets/images/facebook6.jpeg','assets/images/facebook7.jpeg']),
      'confirmed' => 1
    ];
});

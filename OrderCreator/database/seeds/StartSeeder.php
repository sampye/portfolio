<?php

use Illuminate\Database\Seeder;

class StartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries = array(
            array('name' => 'Finland'),
            array('name' => 'Poland'),
            array('name' => 'UK')
        );

        $vats = array(
            array('name' => 'Foodstuffs'),
            array('name' => 'General VAT')
        );

        $county_vat = array(
            array('country_id' => 1, 'vat_id' => 1, 'amount' => 14),
            array('country_id' => 1, 'vat_id' => 2, 'amount' => 24),
            array('country_id' => 2, 'vat_id' => 1, 'amount' => 5),
            array('country_id' => 2, 'vat_id' => 2, 'amount' => 23),
            array('country_id' => 3, 'vat_id' => 1, 'amount' => 0),
            array('country_id' => 3, 'vat_id' => 2, 'amount' => 20),
        );

        $items = array(
            array('name' => 'Rye Bread 500g', 'price' => 1.72, 'vat_id' => 1, 'divisible' => 0),
            array('name' => 'Milk 1 litre', 'price' => 0.92, 'vat_id' => 1, 'divisible' => 0),
            array('name' => 'Rice 1 kg', 'price' => 1.76, 'vat_id' => 1, 'divisible' => 0),
            array('name' => 'Eggs 12/pkg', 'price' => 1.74, 'vat_id' => 1, 'divisible' => 0),
            array('name' => 'Tomato 1 kg', 'price' => 2.76, 'vat_id' => 1, 'divisible' => 1),
            array('name' => 'Beef 500g', 'price' => 6.70, 'vat_id' => 1, 'divisible' => 0),
            array('name' => 'Jeans', 'price' => 83.81, 'vat_id' => 2, 'divisible' => 0),
            array('name' => 'Foodstuffs VAT Item', 'price' => 10, 'vat_id' => 1, 'divisible' => 1),
            array('name' => 'General VAT Item', 'price' => 100, 'vat_id' => 2, 'divisible' => 0),
            array('name' => 'Because 10 is nice', 'price' => 10, 'vat_id' => 2, 'divisible' => 0),
        );

        DB::table('countries')->insert($countries);
        DB::table('vats')->insert($vats);
        DB::table('country_vat')->insert($county_vat);
        DB::table('items')->insert($items);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $installation = ServiceCategory::firstOrCreate(
            ['name' => 'Window Installation'],
            ['description' => 'New window installation services', 'is_active' => true],
        );

        $repair = ServiceCategory::firstOrCreate(
            ['name' => 'Window Repair'],
            ['description' => 'Window repair and restoration services', 'is_active' => true],
        );

        $services = [
            [
                'category_id' => $installation->id,
                'name'        => 'uPVC Window Installation',
                'description' => 'New uPVC window installation for homes and commercial spaces.',
                'material'    => 'upvc',
                'is_active'   => true,
            ],
            [
                'category_id' => $installation->id,
                'name'        => 'Aluminium Window Installation',
                'description' => 'New aluminium window installation. Coming soon.',
                'material'    => 'aluminium',
                'is_active'   => false,
            ],
            [
                'category_id' => $installation->id,
                'name'        => 'Facade Window Installation',
                'description' => 'Facade and curtain wall window installation. Coming soon.',
                'material'    => 'facade',
                'is_active'   => false,
            ],
            [
                'category_id' => $repair->id,
                'name'        => 'uPVC Window Repair',
                'description' => 'Repair and restoration of uPVC windows.',
                'material'    => 'upvc',
                'is_active'   => true,
            ],
        ];

        foreach ($services as $data) {
            Service::firstOrCreate(
                ['name' => $data['name'], 'category_id' => $data['category_id']],
                $data,
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Rename existing categories if they still carry the old names
        ServiceCategory::where('name', 'Window Installation')->update(['name' => 'New Window Installation']);
        ServiceCategory::where('name', 'Window Repair')->update(['name' => 'Window Servicing & Repair']);

        $categories = [
            [
                'name'        => 'New Window Installation',
                'description' => 'New window installation services for homes and offices.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Annual Maintenance Contracts (AMC)',
                'description' => 'Structured AMC plans for preventive upkeep and priority support.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Window Servicing & Repair',
                'description' => 'Professional servicing and corrective maintenance for installed window systems.',
                'is_active'   => false,
            ],
            [
                'name'        => 'Glass Replacement',
                'description' => 'Replace broken or damaged window glass panels quickly and safely.',
                'is_active'   => false,
            ],
            [
                'name'        => 'Handle Repair',
                'description' => 'Fix or replace window handles, locks, and hardware parts.',
                'is_active'   => false,
            ],
            [
                'name'        => 'Weather Sealing',
                'description' => 'Improve insulation and weatherproofing for existing windows.',
                'is_active'   => false,
            ],
            [
                'name'        => 'Façade & Window Cleaning',
                'description' => 'Professional cleaning for façades, glazing, and aluminium/uPVC frames.',
                'is_active'   => false,
            ],
            [
                'name'        => 'Inspection & Assessment',
                'description' => 'Professional inspection and damage assessment before execution.',
                'is_active'   => false,
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $data) {
            $categoryModels[$data['name']] = ServiceCategory::updateOrCreate(
                ['name' => $data['name']],
                ['description' => $data['description'], 'is_active' => $data['is_active']],
            );
        }

        $installation = $categoryModels['New Window Installation'];
        $repair       = $categoryModels['Window Servicing & Repair'];

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

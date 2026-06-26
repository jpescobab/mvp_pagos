<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CoreInstitucionalSeeder::class);
        $this->call(JurisdiccionesSeeder::class);
        $this->call(CfinancierosSeeder::class);
        $this->call(CcostosSeeder::class);

        $this->call(ItemsSeeder::class);
        $this->call(AsignacionesSeeder::class);
        $this->call(CatalogosSeeder::class);
        $this->call(ProveedoresSeeder::class);
        $this->call(ClientesMedidoresSeeder::class);

        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(DocumentTypesSeeder::class);

        // User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $testUser->assignRole('superadmin');
    }
}

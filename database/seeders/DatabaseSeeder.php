<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        $this->call(TiposDocumentoSeeder::class);
        $this->call(WorkflowPagoProveedoresSeeder::class);
        $this->call(RequisitosDocumentalesPagoProveedoresSeeder::class);
        $this->call(IntegracionesSeeder::class);
        $this->call(WorkflowInformesRazonadosSeeder::class);
        $this->call(ModalidadesAdquisicionSeeder::class);
        $this->call(WorkflowAdquisicionesSeeder::class);
        $this->call(RequisitosDocumentalesAdquisicionesSeeder::class);

        $this->call(FuncionariosCapjSeeder::class);

        // User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'sadmin',
            'email' => 'sadmin@pjud.cl',
            'password' => Hash::make('sadmin123'),
        ]);
        $testUser->assignRole('superadmin');
    }
}

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
        $this->call(PresupuestoSeeder::class);
        $this->call(TiposProcesoPagoSeeder::class);
        $this->call(RequisitosDocumentalesPagoProveedoresSeeder::class);
        $this->call(IntegracionesSeeder::class);
        $this->call(WorkflowInformesRazonadosSeeder::class);
        $this->call(ModalidadesAdquisicionSeeder::class);
        $this->call(WorkflowAdquisicionesSeeder::class);
        $this->call(RequisitosDocumentalesAdquisicionesSeeder::class);
        $this->call(WorkflowPresupuestoCdpSeeder::class);

        // Snapshot de desarrollo (UF/USD/UTM/UTA/IPC ene-ago 2026) para no
        // golpear la API de la CMF en cada fresh — ver docblock del seeder.
        // Quitar cuando ya no haga falta reproducir este período localmente.
        $this->call(IndicadoresEconomicosDesarrolloSeeder::class);

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

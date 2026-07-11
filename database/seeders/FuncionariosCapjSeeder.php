<?php

namespace Database\Seeders;

use App\Models\Ccosto;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FuncionariosCapjSeeder extends Seeder
{
    /**
     * Contraseña compartida de todos los usuarios de prueba sembrados por
     * este seeder. Solo para desarrollo local.
     */
    private const PASSWORD = 'capj2026dev';

    /**
     * Ccosto real al que pertenecen todos los cargos de esta nómina
     * (Administración Zonal Coyhaique). Ver database/seeders/CcostosSeeder.php.
     */
    private const CODIGO_CCOSTO = '1400010201';

    /**
     * Mapea el cargo real (columna ROL de la nómina) al rol de Spatie que le
     * corresponde en la app. Los cargos ausentes de este mapa quedan sin rol
     * especial — usuarios autenticados base, útiles para probar accesos
     * denegados en pantallas de pago_proveedores.
     *
     * @var array<string, string>
     */
    private const CARGO_A_ROL = [
        'ADMINISTRADOR ZONAL' => 'administrador_zonal',
        'JEFE SECCION FINANZAS Y PRESUPUESTO' => 'jefe_finanzas',
        'ADMINISTRATIVO DE CONTABILIDAD' => 'administrativo_finanzas',
        'ADMINISTRATIVO DE FINANZAS' => 'administrativo_finanzas',
        'ASISTENTE DE CUENTAS CORRIENTES' => 'administrativo_finanzas',
        'JEFE SECCION ADQUISICIONES Y MANTENIMIENTO' => 'administrativo_adquisiciones',
        'ADMINISTRATIVO DE ADQUISICIONES' => 'administrativo_adquisiciones',
        'BODEGUERO' => 'administrativo_adquisiciones',
    ];

    /**
     * Siembra usuarios de prueba con roles y permisos realistas a partir de
     * la nómina real de Administración Zonal Coyhaique. Requiere
     * database/seeders/data/funcionarios-capj.local.php, un archivo local no
     * versionado (el repo es público) — sin él, no hace nada.
     */
    public function run(): void
    {
        $ruta = database_path('seeders/data/funcionarios-capj.local.php');

        if (! file_exists($ruta)) {
            $this->command->comment(
                'FuncionariosCapjSeeder: omitido, falta database/seeders/data/funcionarios-capj.local.php (dato local, no versionado).',
            );

            return;
        }

        $ccostoId = Ccosto::where('codigo', self::CODIGO_CCOSTO)->value('id');
        $cfinancieroId = Ccosto::where('codigo', self::CODIGO_CCOSTO)->value('cfinanciero_id');

        /** @var list<array{unidad: string, cargo: string, rut: string, nombre: string, correo: string}> $nomina */
        $nomina = require $ruta;

        foreach ($nomina as $persona) {
            $user = User::firstOrCreate(
                ['email' => $persona['correo']],
                ['name' => $persona['nombre'], 'password' => Hash::make(self::PASSWORD)],
            );

            Funcionario::updateOrCreate(
                ['rut' => $persona['rut']],
                [
                    'nombre' => $persona['nombre'],
                    'cargo' => $persona['cargo'],
                    'unidad' => $persona['unidad'],
                    'user_id' => $user->id,
                    'ccosto_id' => $ccostoId,
                    'cfinanciero_id' => $cfinancieroId,
                    'activo' => true,
                ],
            );

            $rol = self::CARGO_A_ROL[$persona['cargo']] ?? null;

            if ($rol !== null && ! $user->hasRole($rol)) {
                $user->assignRole($rol);
            }
        }

        $this->command->info(sprintf(
            'FuncionariosCapjSeeder: %d usuarios sembrados (contraseña: %s).',
            count($nomina),
            self::PASSWORD,
        ));
    }
}

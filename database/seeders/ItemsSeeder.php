<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemsSeeder extends Seeder
{
    /**
     * Seed the real budget classifier items (subtitulo 22).
     */
    public function run(): void
    {
        $items = [
            ['codigo' => '2201', 'nombre' => 'Alimentos y Bebidas', 'descripcion' => 'Son los gastos que por estos conceptos se realizan para la alimentación de funcionarios, alumnos, reclusos y demás personas, con derecho a estos beneficios de acuerdo con las leyes y los reglamentos vigentes, a excepción de las raciones otorgadas en dinero, las que se imputarán al respectivo ítem de Gastos en Personal. Incluye, además, los gastos que por concepto de alimentación de animales, corresponda realizar.'],
            ['codigo' => '2202', 'nombre' => 'Textiles, Vestuario y Calzado', 'descripcion' => 'Son los gastos por concepto de adquisiciones y/o confecciones de textiles, acabados textiles, vestuarios y sus accesorios, prendas diversas de vestir y calzado.'],
            ['codigo' => '2203', 'nombre' => 'Combustibles y Lubricantes', 'descripcion' => 'Son los gastos por concepto de adquisiciones de combustibles y lubricantes para el consumo de vehículos, maquinarias, equipos de producción, tracción y elevación, calefacción y otros usos. Se incluye, además, otros gastos, tales como pago a remolcadores, lanchaje, sobretiempo de cuadrillas marítimas, viáticos, movilización, etc., que los proveedores facturen a los diversos servicios y que se originen exclusivamente por entrega y recepción de combustibles y lubricantes, cuando interviene en estas faenas personal ajeno a los distintos servicios. Comprende: a)Gasolina especial, corriente, de aviación y otros usos. b) Petróleo crudo, combustibles Nº 5 y 6 diésel, bunjers y otros petróleos. c) Otros combustibles como kerosene, nafta disolvente, tractorina, turbofuel, metanol, carbón vegetal y mineral y otros similares no calificados anteriormente. Se excluyen las adquisiciones de gas de cañería y/o licuado, las que deberán hacerse con cargo al ítem 05. No obstante, dichas adquisiciones, cuando se utilicen en la preparación de alimentos, tratándose de Servicios que deban proporcionarlos en forma masiva, podrán imputarse a la asignación que corresponda de este ítem. d) Lubricantes para lavado, motores, cajas de transmisión, diferenciales, rodamientos, engranajes, ferreterías y otros usos. Incluye, además, las adquisiciones de grasas, líquidos para frenos y demás lubricantes para equipos de transportes y usos agrícolas e industriales.'],
            ['codigo' => '2204', 'nombre' => 'Materiales de Uso o Consumo', 'descripcion' => 'Son los gastos por concepto de adquisiciones de materiales de uso o consumo corriente, tales como materiales de oficina, materiales de enseñanza, productos químicos y farmacéuticos, materiales y útiles quirúrgicos y útiles de aseo, menaje para casinos y oficinas, insumos computacionales, materiales y repuestos y accesorios para mantenimientos y reparaciones, para la dotación de los organismos del Sector Público.'],
            ['codigo' => '2205', 'nombre' => 'Servicios Básicos', 'descripcion' => 'Son los gastos por concepto de consumos de energía eléctrica, agua potable, derechos de agua, compra de agua a particulares, gas de cañería y licuado, correo, servicios telefónicos y otros relacionados con la transmisión de voz y datos. Corresponde registrar aquí el interés que corresponda por la mora en el pago, cuando sea procedente.'],
            ['codigo' => '2206', 'nombre' => 'Mantenimiento y Reparaciones', 'descripcion' => 'Son los gastos por servicios que sean necesarios efectuar por concepto de reparaciones y mantenimiento de bienes muebles e inmuebles, instalaciones, construcciones menores y sus artículos complementarios como cortinajes, persianas, rejas de fierro, toldos y otros similares. En caso de que el cobro de la prestación de servicios incluya el valor de los materiales incorporados, el gasto total se imputará a este ítem, en la asignación que corresponda.'],
            ['codigo' => '2207', 'nombre' => 'Publicidad y Difusión', 'descripcion' => 'Son los gastos por concepto de publicidad, difusión o relaciones públicas en general, tales como avisos, promoción en periódicos, radios, televisión, cines, teatros, revistas, contratos con agencias publicitarias, servicios de exposiciones y, en general, todo gasto similar que se destine a estos objetivos, sujeto a la normativa del artículo 3º de la Ley Nº 19.896.'],
            ['codigo' => '2208', 'nombre' => 'Servicios Generales', 'descripcion' => 'Servicios Generales'],
            ['codigo' => '2209', 'nombre' => 'Arriendos', 'descripcion' => 'Arriendos'],
            ['codigo' => '2210', 'nombre' => 'Servicios Financieros y de Seguros', 'descripcion' => 'Servicios Financieros y de Seguros'],
            ['codigo' => '2211', 'nombre' => 'Servicios Técnicos y Profesionales', 'descripcion' => 'Servicios Técnicos y Profesionales'],
            ['codigo' => '2212', 'nombre' => 'Otros Gastos en Bienes y Servicios de Consumo', 'descripcion' => 'Otros Gastos en Bienes y Servicios de Consumo'],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(['codigo' => $item['codigo']], ['nombre' => $item['nombre'], 'descripcion' => $item['descripcion']]);
        }
    }
}

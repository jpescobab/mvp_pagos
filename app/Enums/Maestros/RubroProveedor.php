<?php

namespace App\Enums\Maestros;

enum RubroProveedor: string
{
    case AlimentosBebidas = 'alimentos_bebidas';
    case TextilesVestuario = 'textiles_vestuario';
    case CombustiblesLubricantes = 'combustibles_lubricantes';
    case InsumosOficina = 'insumos_oficina';
    case TecnologiaInformatica = 'tecnologia_informatica';
    case MantencionInstalaciones = 'mantencion_instalaciones';
    case TransporteLogistica = 'transporte_logistica';
    case AsesoriaProfesional = 'asesoria_profesional';
    case Mobiliario = 'mobiliario';
    case ImprentaPublicidad = 'imprenta_publicidad';
    case ArriendoVehiculosMaquinaria = 'arriendo_vehiculos_maquinaria';
    case Construccion = 'construccion';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::AlimentosBebidas => 'Alimentos y bebidas',
            self::TextilesVestuario => 'Textiles, vestuario y calzado',
            self::CombustiblesLubricantes => 'Combustibles y lubricantes',
            self::InsumosOficina => 'Insumos de oficina',
            self::TecnologiaInformatica => 'Tecnología e informática',
            self::MantencionInstalaciones => 'Mantención de instalaciones y equipos',
            self::TransporteLogistica => 'Transporte y logística',
            self::AsesoriaProfesional => 'Asesoría y servicios profesionales',
            self::Mobiliario => 'Mobiliario y diseño',
            self::ImprentaPublicidad => 'Imprenta y publicidad',
            self::ArriendoVehiculosMaquinaria => 'Arriendo de vehículos y maquinaria',
            self::Construccion => 'Construcción',
            self::Otro => 'Otro',
        };
    }
}

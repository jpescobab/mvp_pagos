// Ejecutar con: node --test services/sgf-playwright/nombre-archivo.test.js
// Mismos casos que tests/Feature/Sgf/ReparadorNombreArchivoSgfTest.php.
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { repararMojibake } from './nombre-archivo.js';

const conMojibake = [
    ['CT NÂ°957_433 CONST.EHG.pdf', 'CT N°957_433 CONST.EHG.pdf'],
    ['pasaje_aÃ©reo (8).pdf', 'pasaje_aéreo (8).pdf'],
    ['SOLICITUD_Mabel_CÃ¡rdenas.pdf', 'SOLICITUD_Mabel_Cárdenas.pdf'],
    ['PROYECCIÃ\u0091N.pdf', 'PROYECCIÑN.pdf'],
    ['GarantÃ­a.pdf', 'Garantía.pdf'],
];

const intactos = ['Factura 433.pdf', 'Garantía.pdf', 'Cañería.pdf', 'N°123.pdf', '', 'Resumen – final.pdf', 'Ã solo.pdf', 'PASAJES_AÃ¿REOS.pdf', 'C.JAÃ¿A.pdf', 'AÃ¿REOS_CÃ¡rdenas.pdf'];

for (const [corrupto, esperado] of conMojibake) {
    test(`repara ${JSON.stringify(corrupto)}`, () => {
        assert.equal(repararMojibake(corrupto), esperado);
    });
}

for (const nombre of intactos) {
    test(`conserva intacto ${JSON.stringify(nombre)}`, () => {
        assert.equal(repararMojibake(nombre), nombre);
    });
}

test('es idempotente', () => {
    const una = repararMojibake('CT NÂ°957_433.pdf');

    assert.equal(repararMojibake(una), una);
});

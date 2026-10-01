// Repara el mojibake (UTF-8 interpretado como Latin-1) de los nombres de
// archivo que entrega SGF, p. ej. "NÂ°" -> "N°", "aÃ©reo" -> "aéreo".
//
// Contrato idéntico a app/Services/Sgf/ReparadorNombreArchivoSgf.php: solo
// repara si el nombre contiene "Ã"/"Â", todos sus caracteres caben en
// Latin-1 y la re-decodificación como UTF-8 es válida y distinta. En
// cualquier otro caso devuelve el nombre intacto.
export function repararMojibake(nombre) {
    if (!/[ÃÂ]/.test(nombre)) {
        return nombre;
    }

    if ([...nombre].some((caracter) => caracter.codePointAt(0) > 0xff)) {
        return nombre;
    }

    // "Ã¿"/"Â¿": el byte de continuación ya llegó degradado a "¿"; el
    // roundtrip daría un carácter válido pero equivocado ("ÿ"). No se repara.
    if (/[ÃÂ]¿/.test(nombre)) {
        return nombre;
    }

    try {
        const reparado = new TextDecoder('utf-8', { fatal: true }).decode(Buffer.from(nombre, 'latin1'));

        return reparado === nombre ? nombre : reparado;
    } catch {
        return nombre;
    }
}

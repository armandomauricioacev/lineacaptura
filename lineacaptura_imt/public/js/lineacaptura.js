/**
 * Funciones JavaScript para la página de línea de captura
 * Maneja la descarga y visualización del HTML del SAT
 */

// Variable global para almacenar el contenido HTML
let htmlContentGlobal = null;

/**
 * Inicializa el contenido HTML desde el servidor
 * @param {string} htmlContent - Contenido HTML decodificado del SAT
 */
function initializeHtmlContent(htmlContent) {
    htmlContentGlobal = htmlContent;
}

/**
 * Descarga el HTML decodificado del SAT como archivo
 */
function descargarHTML() {
    if (htmlContentGlobal && htmlContentGlobal.trim() !== '') {
        const blob = new Blob([htmlContentGlobal], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        
        // Generar nombre de archivo con timestamp
        const now = new Date();
        const timestamp = now.getFullYear() + '-' + 
                         String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                         String(now.getDate()).padStart(2, '0') + '_' + 
                         String(now.getHours()).padStart(2, '0') + '-' + 
                         String(now.getMinutes()).padStart(2, '0') + '-' + 
                         String(now.getSeconds()).padStart(2, '0');
        
        a.download = 'documento_sat_' + timestamp + '.html';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    } else {
        alert('No hay contenido HTML disponible para descargar.');
    }
}

/**
 * Abre el HTML decodificado en una nueva ventana
 */
function abrirEnNuevaVentana() {
    if (htmlContentGlobal && htmlContentGlobal.trim() !== '') {
        const nuevaVentana = window.open('', '_blank');
        if (nuevaVentana) {
            nuevaVentana.document.write(htmlContentGlobal);
            nuevaVentana.document.close();
        } else {
            alert('No se pudo abrir la nueva ventana. Verifica que no esté bloqueada por el navegador.');
        }
    } else {
        alert('No hay contenido HTML disponible para mostrar.');
    }
}

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Funciones de línea de captura cargadas correctamente');
});
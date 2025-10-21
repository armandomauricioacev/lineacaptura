<?php

namespace App\Services;

use Exception;

class HtmlDecoderService
{
    /**
     * Función para decodificar HTML base64 con soporte para caracteres especiales en español
     * Implementación basada en validador_linea_de_captura.php
     */
    public static function decodificarHtmlBase64($codigoBase64)
    {
        try {
            // Usar la misma implementación que en validador_linea_de_captura.php
            $htmlDecodificado = html_entity_decode(base64_decode($codigoBase64), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Verificar si la decodificación fue exitosa
            if ($htmlDecodificado === false || empty($htmlDecodificado)) {
                return "Error: No se pudo decodificar el código base64.";
            }
            
            return $htmlDecodificado;
            
        } catch (Exception $e) {
            return "Error al decodificar: " . $e->getMessage();
        }
    }

    /**
     * Función para obtener y decodificar el contenido del archivo codigo.json
     */
    public static function obtenerYDecodificarCodigo()
    {
        try {
            $rutaArchivo = resource_path('views/forms/codigo.json');
            
            if (!file_exists($rutaArchivo)) {
                return [
                    'error' => true,
                    'mensaje' => 'El archivo codigo.json no existe en la ruta especificada.'
                ];
            }
            
            $contenidoArchivo = file_get_contents($rutaArchivo);
            
            // Limpiar el contenido de posibles caracteres problemáticos
            $contenidoArchivo = trim($contenidoArchivo);
            
            // Verificar si el JSON está completo
            if (substr($contenidoArchivo, -1) !== '}') {
                return [
                    'error' => true,
                    'mensaje' => 'El archivo JSON está incompleto o truncado. Debe terminar con "}".',
                    'debug_info' => [
                        'tamaño_archivo' => strlen($contenidoArchivo),
                        'ultimos_50_caracteres' => substr($contenidoArchivo, -50)
                    ]
                ];
            }
            
            $datosJson = json_decode($contenidoArchivo, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error' => true,
                    'mensaje' => 'Error al decodificar el JSON: ' . json_last_error_msg(),
                    'debug_info' => [
                        'codigo_error' => json_last_error(),
                        'tamaño_archivo' => strlen($contenidoArchivo),
                        'primeros_100_caracteres' => substr($contenidoArchivo, 0, 100),
                        'ultimos_100_caracteres' => substr($contenidoArchivo, -100)
                    ]
                ];
            }
            
            if (!isset($datosJson['Acuse']['HTML'])) {
                return [
                    'error' => true,
                    'mensaje' => 'No se encontró el campo HTML en el JSON.',
                    'debug_info' => [
                        'claves_disponibles' => array_keys($datosJson),
                        'estructura_acuse' => isset($datosJson['Acuse']) ? array_keys($datosJson['Acuse']) : 'No existe'
                    ]
                ];
            }
            
            $codigoBase64 = $datosJson['Acuse']['HTML'];
            $htmlDecodificado = self::decodificarHtmlBase64($codigoBase64);
            
            return [
                'error' => false,
                'datos_originales' => $datosJson,
                'codigo_base64' => $codigoBase64,
                'html_decodificado' => $htmlDecodificado
            ];
            
        } catch (Exception $e) {
            return [
                'error' => true,
                'mensaje' => 'Error al procesar el archivo: ' . $e->getMessage()
            ];
        }
    }
}
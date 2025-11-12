<?php

namespace App\Helpers;

class AlegraErrorHelper
{
    /**
     * Códigos de error conocidos de Alegra
     */
    const ERROR_CODES = [
        3061 => 'La numeración debe estar activa',
        3062 => 'La forma de pago es obligatoria',
        3063 => 'El cliente no fue encontrado',
        3064 => 'El producto no fue encontrado',
        3065 => 'La plantilla de numeración no es válida',
    ];

    /**
     * Obtiene un mensaje de error amigable basado en el mensaje de error de Alegra
     *
     * @param string $errorMessage Mensaje de error original
     * @param int|null $errorCode Código de error (opcional)
     * @return string Mensaje de error amigable
     */
    public static function getFriendlyErrorMessage($errorMessage, $errorCode = null)
    {
        // Errores conocidos por mensaje
        $knownErrors = [
            'La forma de pago es obligatoria' => 'Error en la forma de pago. Verifica que el formato sea correcto: payment: { paymentMethod: { id: 10 }, account: { id: 1 } }',
            'La numeración debe estar activa' => 'La plantilla de numeración electrónica no está activa en Alegra. Verifica en la configuración de Alegra.',
            'El cliente no fue encontrado' => 'No se encontró el cliente en Alegra. Asegúrate de que el cliente esté sincronizado correctamente.',
            'El producto no fue encontrado' => 'No se encontró el producto en Alegra. Asegúrate de que el producto esté sincronizado correctamente.',
            'No se pudo crear la factura' => 'No se pudo crear la factura en Alegra. Verifica los datos enviados.',
        ];

        // Si tenemos un código de error conocido
        if ($errorCode && isset(self::ERROR_CODES[$errorCode])) {
            return self::ERROR_CODES[$errorCode] . ' (Código: ' . $errorCode . ')';
        }

        // Buscar por mensaje exacto
        if (isset($knownErrors[$errorMessage])) {
            return $knownErrors[$errorMessage];
        }

        // Buscar por coincidencia parcial
        foreach ($knownErrors as $key => $value) {
            if (stripos($errorMessage, $key) !== false) {
                return $value;
            }
        }

        // Mensaje genérico si no se encuentra coincidencia
        return 'Error en la integración con Alegra: ' . $errorMessage;
    }

    /**
     * Analiza la respuesta de error de Alegra y devuelve un mensaje amigable
     *
     * @param array|string $response Respuesta de error de Alegra
     * @return string Mensaje de error amigable
     */
    public static function parseErrorResponse($response)
    {
        // Si es un string, intentar decodificar JSON
        if (is_string($response)) {
            $response = json_decode($response, true);
        }

        // Si es un array con estructura de error de Alegra
        if (is_array($response)) {
            if (isset($response['message'])) {
                return self::getFriendlyErrorMessage($response['message'], $response['code'] ?? null);
            } elseif (isset($response['error']) && is_string($response['error'])) {
                return self::getFriendlyErrorMessage($response['error']);
            } elseif (isset($response['error']['message'])) {
                return self::getFriendlyErrorMessage($response['error']['message'], $response['error']['code'] ?? null);
            }
        }

        // Si no se puede interpretar, devolver mensaje genérico
        return 'Error desconocido en la integración con Alegra';
    }
}

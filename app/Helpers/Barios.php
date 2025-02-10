<?php

namespace App\Helpers;

class Barios
{
    function deleteFilesSubfoldersPath($ruta)
    {
        $ruta = public_path('storage' . DIRECTORY_SEPARATOR . $ruta);
        foreach (glob($ruta . "/*") as $archivo) {
            if (is_dir($archivo)) {
                $this->deleteFilesSubfoldersPath($archivo); // Elimina subcarpetas
                rmdir($archivo); // Elimina la carpeta vacía
            } else {
                unlink($archivo); // Elimina archivos
            }
        }
    }
    function deleteFilesStartingWith($ruta, $prefix)
    {
        // Verificar si la ruta existe
        if (!file_exists($ruta)) {
            return "La ruta no existe: $ruta";
        }

        // Verificar si es un directorio
        if (!is_dir($ruta)) {
            return "La ruta no es un directorio: $ruta";
        }
        $msg = [];
        // Recorrer todos los elementos en la carpeta
        foreach (glob($ruta . "/*") as $archivo) {
            // Obtener el nombre del archivo (sin la ruta)
            $nombreArchivo = basename($archivo);

            // Verificar si el nombre del archivo comienza con el prefijo
            if (strpos($nombreArchivo, $prefix) === 0) {
                if (is_file($archivo)) {
                    // Si es un archivo, eliminarlo
                    if (unlink($archivo)) {
                        array_push($msg, "Archivo eliminado: $archivo");
                    } else {
                        array_push($msg,  "No se pudo eliminar el archivo: $archivo");
                    }
                } else {
                    array_push($msg, "$archivo no es un archivo (es una carpeta)");
                }
            } else {
                array_push($msg,  "$archivo no comienza con '$prefix'.");
            }
        }
        return $msg;
    }

    public function deleteFile($file)
    {
        $file = public_path('storage' . DIRECTORY_SEPARATOR . $file);
        if (file_exists($file)) {
            unlink($file);
            return "El archivo ha sido eliminado.";
        } else {
            return "El archivo no existe.";
        }
    }
}

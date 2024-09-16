<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProcesadorDatos
{
    private $ipap_result_array;
    private $osep_result_array;
    private $resultado_faltantes_inscriptos;
    private $resultado_faltantes_no_inscriptos;

    public function __construct()
    {
        // Inicializar arrays
        $this->ipap_result_array = [];
        $this->osep_result_array = [];
        $this->resultado_faltantes_inscriptos = [];
        $this->resultado_faltantes_no_inscriptos = [];
    }

    public function obtener_dni($cuil)
    {
        $dni = '';
        if(strlen($cuil) == 11){
            // Elimina los 2 primeros caracteres
            $cuil_sin_primero = substr($cuil, 2);

            // Elimina el último caracter
            return substr($cuil_sin_primero, 0, -1);
        }

        if(strlen($cuil) == 8){
            $dni = $cuil;
            return $dni;
        }

        return $dni;
    }

    public function faltantes_no_inscriptos()
    {
        $csv_ipap = fopen('doc/vinscripcion_prod.csv', 'r');
        //$csv_ipap = fopen('doc/ipap_inscripcions_ley_micael_test.csv', 'r');
        //$csv_osep_personas_total = fopen('doc/osep_persona_test.csv', 'r');
        $csv_osep_personas_total = fopen('doc/Archivo 1 Proceso 1241 Febrero 2024.csv', 'r');

        try {
            $primera_fila = true;

            while (($row_ipap = fgetcsv($csv_ipap, 0, ';')) !== false) {
                if ($primera_fila) {
                    $primera_fila = false;
                    continue;
                }
                // Obtener el cuil de la fila actual
                $cuil = $row_ipap[4];
                
                // Verificar si el cuil ya existe en el array
                if (!isset($cuils_ipap[$cuil])) {
                    // Guardar el cuil como clave en el array
                    $cuils_ipap[$cuil] = true;
                }
            }

            $primera_fila_2 = true;
            $key = -1;

            // Crea un nuevo objeto Spreadsheet
            $spreadsheet = new Spreadsheet();

            // Itera sobre el archivo CSV
            while (($fila = fgetcsv($csv_osep_personas_total, 0, ';')) !== false) {
                if ($primera_fila_2) {
                    $primera_fila_2 = false;
                    continue;
                }

                // Obtén el DNI de la fila actual
                $cuil = $fila[0]; // Suponiendo que la tercera columna contiene el DNI

                if (!isset($cuils_osep[$cuil])) {
                    if(!isset($cuils_ipap[$cuil]) && !empty($cuil)){
                        // Crea una nueva hoja de cálculo
                        $hoja = $spreadsheet->getActiveSheet();

                        // Definir encabezados de columna
                        $hoja->setCellValue('A1', 'NOMBRE');
                        $hoja->setCellValue('B1', 'APELLIDO');
                        $hoja->setCellValue('C1', 'DNI');
                        $hoja->setCellValue('D1', 'CAR');
                        $hoja->setCellValue('E1', 'JUR');
                        $hoja->setCellValue('F1', 'UOR');
                        $hoja->setCellValue('G1', 'DESCRIPCIÓN');

                        $nombre_apelldio = $fila[1];
                        $parts = explode(", ", $nombre_apelldio);
                        $apellido = '';
                        $nombre = '';

                        // Si hay dos partes
                        if (count($parts) === 2) {
                            $apellido = $parts[0];
                            $nombre = $parts[1];
                        }

                        $cuil = $fila[0];
                        $row = $key + 2;
                        $key = $key + 1;
                        
                        // Escribir los datos en las celdas correspondientes
                        $hoja->setCellValue('A' . $row, $nombre); // nombre
                        $hoja->setCellValue('B' . $row, $apellido); // apellido 
                        $hoja->setCellValue('C' . $row, $this->obtener_dni($cuil)); // dni
                        $hoja->setCellValue('D' . $row, $fila[8]); // car
                        $hoja->setCellValue('E' . $row, $fila[9]); // jur
                        $hoja->setCellValue('F' . $row, $fila[10]); // uorg
                        $hoja->setCellValue('G' . $row, $fila[13]); // descripción
                    }
                    // Guardar el cuil como clave en el array
                    $cuils_osep[$cuil] = true;
                }
                unset($fila);
            }

            // Cierra el archivo CSV
            fclose($csv_osep_personas_total);

            // Crear un objeto Writer para Excel (Xlsx)
            $writer = new Xlsx($spreadsheet);
            $writer->save('resultado_faltantes_no_inscriptos.xlsx');

            echo "Archivo Excel generado correctamente.";

            return true;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function faltantes_inscriptos()
    {
        $csv_ipap = fopen('doc/vinscripcion_prod.csv', 'r');
        //$csv_ipap = fopen('doc/ipap_inscripcions_ley_micael_test.csv', 'r');
        //$csv_osep_personas_total = fopen('doc/osep_persona_test.csv', 'r');
        $csv_osep_personas_total = fopen('doc/Archivo 1 Proceso 1241 Febrero 2024.csv', 'r');

        try {
            $primera_fila = true;

            while (($row_ipap = fgetcsv($csv_ipap, 0, ';')) !== false) {
                if ($primera_fila) {
                    $primera_fila = false;
                    continue;
                }
                // Obtener el cuil de la fila actual
                $cuil = $row_ipap[4];
                $estado_id = $row_ipap[17];
                $estado_id = (int) $estado_id;
                
                // Verificar si el cuil ya existe en el array
                if (!isset($cuils_ipap[$cuil]) && $estado_id != 1) {
                    // Guardar el cuil como clave en el array
                    $cuils_ipap[$cuil] = $row_ipap;
                }
            }
            
            $primera_fila_2 = true;
            $key = 0;

            // Crea un nuevo objeto Spreadsheet
            $spreadsheet = new Spreadsheet();

            // Itera sobre el archivo CSV
            while (($fila = fgetcsv($csv_osep_personas_total, 0, ';')) !== false) {
                if ($primera_fila_2) {
                    $primera_fila_2 = false;
                    continue;
                }

                // Obtén el DNI de la fila actual
                $cuil = $fila[0]; // Suponiendo que la tercera columna contiene el DNI

                if (!isset($cuils_osep[$cuil])) {
                    // Guardar el cuil como clave en el array               
                    if(isset($cuils_ipap[$cuil]) && !empty($cuil)){
                        // Crea una nueva hoja de cálculo
                        $hoja = $spreadsheet->getActiveSheet();

                        // Definir encabezados de columna
                        $hoja->setCellValue('A1', 'nombre');
                        $hoja->setCellValue('B1', 'apellido');
                        $hoja->setCellValue('C1', 'dni');
                        $hoja->setCellValue('D1', 'car');
                        $hoja->setCellValue('E1', 'jur');
                        $hoja->setCellValue('F1', 'u_org');
                        $hoja->setCellValue('G1', 'cardenominacion');
                        $hoja->setCellValue('H1', 'jurdenominacion');
                        $hoja->setCellValue('I1', 'uodenominacion');
                        $hoja->setCellValue('J1', 'email');

                        $nombre = $cuils_ipap[$cuil][3];
                        $apellido = $cuils_ipap[$cuil][2];
                        $cuil = $cuils_ipap[$cuil][4];
                        $dni = $this->obtener_dni($cuil);
                        $car = $cuils_ipap[$cuil][10];
                        $jur = $cuils_ipap[$cuil][11];
                        $uor = $cuils_ipap[$cuil][12];
                        $car_name = $cuils_ipap[$cuil][14];
                        $jur_name = $cuils_ipap[$cuil][15];
                        $uor_name = $cuils_ipap[$cuil][16];
                        $email = $cuils_ipap[$cuil][7];

                        $row = $key + 1;
                        $key = $key + 1;
                        
                        // Escribir los datos en las celdas correspondientes
                        $hoja->setCellValue('A' . $row, $nombre);
                        $hoja->setCellValue('B' . $row, $apellido);
                        $hoja->setCellValue('C' . $row, $dni);
                        $hoja->setCellValue('D' . $row, $car);
                        $hoja->setCellValue('E' . $row, $jur);
                        $hoja->setCellValue('F' . $row, $uor);
                        $hoja->setCellValue('G' . $row, $car_name);
                        $hoja->setCellValue('H' . $row, $jur_name);
                        $hoja->setCellValue('I' . $row, $uor_name);
                        $hoja->setCellValue('J' . $row, $email);
                    }

                    $cuils_osep[$cuil] = true;
                }

                unset($fila);
            }

            // Cierra el archivo CSV
            fclose($csv_osep_personas_total);

            // Crear un objeto Writer para Excel (Xlsx)
            $writer = new Xlsx($spreadsheet);

            // Guardar el archivo Excel en el disco
            $writer->save('resultado_faltantes_inscriptos.xlsx');

            echo "Archivo Excel generado correctamente.";

            return true;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function procesarDatos()
    {
        // Abre el archivo CSV de osep_data
        $this->faltantes_no_inscriptos();

        // Abre el archivo CSV de vinscripcion_data
        $this->faltantes_inscriptos();

    }
}

// Crear una instancia de la clase y ejecutar los métodos
$procesador = new ProcesadorDatos();
$procesador->procesarDatos();
?>

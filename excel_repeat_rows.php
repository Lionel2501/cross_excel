<?php
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    class ProcesadorDatos
    {
        public function __construct()
        {
            // Inicializar arrays
        }

        public function procesarDatos()
        {
            //$data_csv = fopen('doc/aprobados_espacio.csv', 'r');
            $data_csv = fopen('doc/aprobados_espacio_administracion.csv', 'r');

            try {
                $primera_fila = true;
                $key = -1;

                // Crea un nuevo objeto Spreadsheet
                $spreadsheet = new Spreadsheet();

                $cuil_repetidos = 0;

                // Itera sobre el archivo CSV
                while (($fila = fgetcsv($data_csv, 0, ';')) !== false) {
                    if ($primera_fila) {
                        $primera_fila = false;
                        continue;
                    }

                    // Obtén el DNI de la fila actual
                    $cuil = $fila[3]; // Suponiendo que la tercera columna contiene el DNI

                    if (isset($cuil_no_repetido[$cuil])) {
                        if(isset($cuil_no_repetido[$cuil]) && $cuil){
                            // Crea una nueva hoja de cálculo
                            $hoja = $spreadsheet->getActiveSheet();

                            // Definir encabezados de columna
                            $hoja->setCellValue('A1', 'ID');
                            $hoja->setCellValue('B1', 'APELLIDO');
                            $hoja->setCellValue('C1', 'NOMBRE');
                            $hoja->setCellValue('D1', 'CUIL');
                            
                            $row = $key + 2;
                            $key = $key + 1;
                            
                            // Escribir los datos en las celdas correspondientes
                            $hoja->setCellValue('A' . $row, $fila[0]); // nombre
                            $hoja->setCellValue('B' . $row, $fila[1]); // apellido 
                            $hoja->setCellValue('C' . $row, $fila[2]); // dni
                            $hoja->setCellValue('D' . $row, $fila[3]); // car

                            $cuil_repetidos = $cuil_repetidos + 1;
                        }
                    } else {
                        // Guardar el cuil como clave en el array
                        $cuil_no_repetido[$cuil] = true;
                    }
                    unset($fila);
                }

                // Cierra el archivo CSV
                fclose($data_csv);

                // Crear un objeto Writer para Excel (Xlsx)
                $writer = new Xlsx($spreadsheet);
                $writer->save('resultados_repetidos.xlsx');

                echo "Archivo Excel generado correctamente.";
                echo "cuil repetido " . $cuil_repetidos;
                echo "cuil_no_repetido " . count($cuil_no_repetido);

                return true;
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }

    // Crear una instancia de la clase y ejecutar los métodos
    $procesador = new ProcesadorDatos();
    $procesador->procesarDatos();
?>

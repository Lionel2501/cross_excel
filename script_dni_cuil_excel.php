<?php
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    class ProcesadorDatos
    {
        public function __construct()
        {
        }

        public function execute()
        {
            // Abrir el archivo CSV
            $csv_dni = fopen('doc/dni_list.csv', 'r');

            try {
                $primera_fila = true;
                $fila = 2;

                // Crear instancia de Spreadsheet
                $spreadsheet = new Spreadsheet();
                $hoja = $spreadsheet->getActiveSheet();
                $hoja->setCellValue('A1', 'DNI');
                $hoja->setCellValue('B1', 'SEXO');
                $hoja->setCellValue('C1', 'CUIL');

                while (($row = fgetcsv($csv_dni, 0, ';')) !== false) {
                    if ($primera_fila) {
                        $primera_fila = false;
                        continue;
                    }

                    // Obtener el DNI de la fila actual
                    $dni = $row[1];
                    $sexo = $row[0];

                    // Realizar la consulta a la API
                    $respuesta_api = $this->consultarAPI($dni, $sexo);

                    // Verificar la respuesta de la API y extraer el CUIL si es válida
                    if ($respuesta_api['response']["Datos"] && !empty($respuesta_api['response']["Datos"]["cuil"])) {
                        $cuil = $respuesta_api['response']["Datos"]["cuil"];
                        $sexo = $respuesta_api['response']["Datos"]["sexo"];
                        $dni = $respuesta_api['response']["Datos"]["dni"];

                        // Escribir los datos en las celdas correspondientes
                        $hoja->setCellValue('A' . $fila, $dni);
                        $hoja->setCellValue('B' . $fila, $sexo);
                        $hoja->setCellValue('C' . $fila, $cuil);
                        $fila++;
                    } else{
                        $cuil = 'error api';
                        $sexo = $sexo;
                        $dni = $dni;
                        echo $cuil . ' ' . $sexo . ' ' . $dni;

                        // Escribir los datos en las celdas correspondientes
                        $hoja->setCellValue('A' . $fila, $dni);
                        $hoja->setCellValue('B' . $fila, $sexo);
                        $hoja->setCellValue('C' . $fila, $cuil);
                        $fila++;
                    }
                }

                // Crear un objeto Writer para Excel (Xlsx)
                $writer = new Xlsx($spreadsheet);
                $writer->save('resultado_dni.xlsx');

                echo "Archivo Excel generado correctamente.";

                return true;
            } catch (\Throwable $th) {
                // Manejar la excepción
                echo "Error al procesar los datos: " . $th->getMessage();
                return false;
            } finally {
                // Cerrar el archivo CSV
                fclose($csv_dni);
            }
        }

        private function consultarAPI($dni, $sexo)
        {
            // URL de la API
            $url = 'http://dicapprda.mendoza.gov.ar:8080/ssciudadano/rest/wsentidad';
            $sexo = strtoupper($sexo);

            // Datos para la solicitud
            $data = [
                'request' => [
                    'nrodoc' => $dni,
                    'sexo' => $sexo,
                    'usuario' => 'ssciudadano',
                    'token' => '21c66a48802af7d637d18e12969b661fa1e006ca72183ccd1ec0c8a509540e12'
                ]
            ];

            // Convertir datos a formato JSON
            $data_json = json_encode($data);

            // Configurar opciones de la solicitud
            $options = array(
                'http' => array(
                    'header'  => "Content-Type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => $data_json
                )
            );

            // Crear contexto de la solicitud
            $context  = stream_context_create($options);

            // Realizar la solicitud a la API y obtener la respuesta
            $response = file_get_contents($url, false, $context);

            // Decodificar la respuesta JSON
            $respuesta_api = json_decode($response, true);

            return $respuesta_api;
        }

        public function procesarDatos()
        {
            $this->execute();
        }
    }

    // Crear una instancia de la clase y ejecutar los métodos
    $procesador = new ProcesadorDatos();
    $procesador->procesarDatos();
?>

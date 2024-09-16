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

            return false;
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
                    $dni = $this->obtener_dni($cuil);
                    
                    // Verificar si el cuil ya existe en el array
                    if (!isset($dni_ipap_no_repetido[$dni]) && $dni) {
                        // Guardar el dni como clave en el array
                        $dni_ipap_no_repetido[$dni] = true;
                    }
                }

                $primera_fila_2 = true;
                $key = -1;

                $car_array = [
                    1  => "Administración Central",
                    2  => "Organismos Descentralizados",
                    5  => "Otras Entidades",
                    9  => "Entes Reguladores y Otros Organismos"
                ];

                $jur_array = [
                    16  => "Ministerio de Seguridad",
                    15  => "Fiscalía de Estado",
                    14  => "Dcción. Gral. de Escuelas",
                    9  => "Min.Planific e Infraestructura Pública",
                    8  => "Ministerio Salud, Desar. Social y Deportes",
                    7  => "Ministerio Economía y Energía",
                    6  => "Ministerio de Hacienda y Finanzas",
                    3  => "Tribunal de Cuentas",
                    2  => "Poder Judicial",
                    5  => "Ministerio de Gobierno,Trabajo y Justicia",
                    22  => "Ministerio de Cultura y Turismo",
                    23 => "Secretaría de Amb. y Ordenamiento Terr.",
                    26 => "Secretaría de Servicios Públicos "
                ];

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
                    $dni = $this->obtener_dni($cuil);

                    if (!isset($dni_osep_no_repetido[$cuil])) {
                        if(!isset($dni_ipap_no_repetido[$dni]) && $dni){
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

                            $nombre_apelldio = $fila[1];
                            $parts = explode(", ", $nombre_apelldio);
                            $apellido = '';
                            $nombre = '';

                            // Si hay dos partes
                            if (count($parts) === 2) {
                                $apellido = $parts[0];
                                $nombre = $parts[1];
                            }
                            $car_id = $fila[8];
                            $jur_id = $fila[9];

                            $car_desc = isset($car_array[$car_id]) ? $car_array[$car_id] : '';
                            $jur_desc = isset($jur_array[$jur_id]) ? $jur_array[$jur_id] : '';

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
                            $hoja->setCellValue('G' . $row, $car_desc); // car denominacion
                            $hoja->setCellValue('H' . $row, $jur_desc); // jur denominacion
                            $hoja->setCellValue('I' . $row, $fila[11]); // uorg denominacion
                        }
                        // Guardar el cuil como clave en el array
                        $dni_osep_no_repetido[$cuil] = true;
                    }
                    unset($fila);
                }

                // Cierra el archivo CSV
                fclose($csv_osep_personas_total);

                // Crear un objeto Writer para Excel (Xlsx)
                $writer = new Xlsx($spreadsheet);
                $writer->save('resultado_faltantes_no_inscriptos.xlsx');

                echo "Archivo Excel generado correctamente.";
                echo "dni_ipap_no_repetido " . count($dni_ipap_no_repetido);
                echo "dni_osep_no_repetido " . count($dni_osep_no_repetido);

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
            //$csv_osep_personas_total = fopen('doc/Archivo 1 Proceso 1241 Febrero 2024.csv', 'r');

            $opciones = [
                'context' => [
                    'http' => [
                        'header' => 'Content-Type: text/plain; charset=UTF-8',
                    ],
                ],
            ];
            
            $csv_osep_personas_total = fopen('doc/Archivo 1 Proceso 1241 Febrero 2024.csv', 'r', false, stream_context_create($opciones));
            //$csv_osep_personas_total = fopen('doc/Archivo 1 Proceso 1241 Febrero 2024.csv', 'r');
            
            try {
                $primera_fila = true;

                while (($row_ipap = fgetcsv($csv_ipap, 0, ';')) !== false) {
                    if ($primera_fila) {
                        $primera_fila = false;
                        continue;
                    }
                    // Obtener el cuil de la fila actual
                    $cuil = $row_ipap[4];
                    $dni = $this->obtener_dni($cuil);
                    $estado_id = $row_ipap[17];
                    $estado_id = (int) $estado_id;
                    
                    // Verificar si el cuil ya existe en el array
                    if (!isset($rows_ipap_no_repetido[$dni]) && $estado_id != 1) {
                        // Guardar el dni como clave en el array
                        $rows_ipap_no_repetido[$dni] = $row_ipap;
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
                    $dni = $this->obtener_dni($cuil);

                    if (!isset($dni_osep_no_repetido[$dni])) {
                        // Guardar el cuil como clave en el array               
                        if(isset($rows_ipap_no_repetido[$dni]) && !empty($dni)){
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

                            $nombre = $rows_ipap_no_repetido[$dni][3];
                            $apellido = $rows_ipap_no_repetido[$dni][2];
                            $cuil = $rows_ipap_no_repetido[$dni][4];
                            $dni = $this->obtener_dni($cuil);
                            $car = $rows_ipap_no_repetido[$dni][10];
                            $jur = $rows_ipap_no_repetido[$dni][11];
                            $uor = $rows_ipap_no_repetido[$dni][12];
                            $car_name = $rows_ipap_no_repetido[$dni][14];
                            $jur_name = $rows_ipap_no_repetido[$dni][15];
                            $uor_name = $rows_ipap_no_repetido[$dni][16];
                            $email = $rows_ipap_no_repetido[$dni][7];

                            $row = $key + 1;
                            $key = $key + 1;

                            // Escribir los datos en las celdas correspondientes
                            $hoja->setCellValueExplicit('A' . $row, $nombre, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('B' . $row, $apellido, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('C' . $row, $dni, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('D' . $row, $car, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('E' . $row, $jur, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('F' . $row, $uor, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('G' . $row, $car_name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('H' . $row, $jur_name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('I' . $row, $uor_name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            $hoja->setCellValueExplicit('J' . $row, $email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }

                        $dni_osep_no_repetido[$dni] = true;
                    }

                    unset($fila);
                }

                // Cierra el archivo CSV
                fclose($csv_osep_personas_total);

                // Crear un objeto Writer para Excel (Xlsx)
                $writer = new Xlsx($spreadsheet);

                // Guardar el archivo Excel en el disco
                $writer->save('resultado_faltantes_inscriptos.csv');

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
            //$this->faltantes_inscriptos();

        }
    }

    // Crear una instancia de la clase y ejecutar los métodos
    $procesador = new ProcesadorDatos();
    $procesador->procesarDatos();
?>

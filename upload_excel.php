<?php
    try {
        // Abre el archivo CSV de osep_data
        $archivo_csv_osep = fopen('doc/osep_data.csv', 'r');

        // Establece la conexión a la base de datos
        $dsn = 'mysql:host=serviciosportaldev1.mendoza.gov.ar;dbname=serviciosportdb;charset=utf8mb4';
        $usuario = 'serviciosportdba';
        $contraseña = 'pyS4KQFvGo+TE43i';
        
        $conexion = new PDO($dsn, $usuario, $contraseña);
        
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        
        // Prepara la consulta SQL para insertar los datos en la base de datos
        //$consulta = $conexion->prepare("INSERT INTO excel_osep (cuil) VALUES (:cuil)");
        $consulta = $conexion->prepare("INSERT INTO excel_osep 
                (cuil, nombre, apellido, dni, genero) 
            VALUES (:cuil, :nombre, :apellido, :dni, :genero)");
        
        $i = 0;

        // Itera sobre el archivo CSV de OSEP
        while(($fila_osep = fgetcsv($archivo_csv_osep, 0, ';')) != false){
            $i = $i + 1;
            
            $cuil_osep_value = $fila_osep[0];
            $parts = explode(", ", $fila_osep[1]);

            // Si hay dos partes
            if (count($parts) === 2) {
                $apellido = $parts[0];
                $nombre = $parts[1];
            }
/*             $fecha_nacimiento = date('Y-m-d', strtotime($fila_osep[2]));
            $fecha_ingreso = date('Y-m-d', strtotime($fila_osep[7])); */
            $genero = $fila_osep[20];

            // Elimina los 2 primeros caracteres
            $cuil_sin_primero = substr($cuil_osep_value, 2);

            // Elimina el último caracter
            $dni = substr($cuil_sin_primero, 0, -1);

            // Verifica que el valor de la columna sea un cuil
            if(gettype($cuil_osep_value) == 'string' && strlen($cuil_osep_value) == 11){
                // Inserta el valor en la base de datos
                $consulta->bindParam(':cuil', $cuil_osep_value);
                $consulta->bindParam(':nombre', $nombre);
                $consulta->bindParam(':apellido', $apellido);
                $consulta->bindParam(':dni', $dni);
/*                 $consulta->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                $consulta->bindParam(':fecha_ingreso', $fecha_ingreso); */
                $consulta->bindParam(':genero', $genero);
                $consulta->execute();
            }
        }
        echo "Los datos se han insertado correctamente en la base de datos.";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
?>

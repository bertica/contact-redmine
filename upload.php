<?php
    session_start();
    
    require_once('secret.php');
    $logFile = fopen("log.txt", 'a');

    // Store all errors
    $errors = [];

    // Available file extensions
    $fileExtensions = ['jpeg','jpg','png','gif'];

   if(!empty($_FILES['fileAjax'] ?? null)) {
        // Recojo form 
        $fileName = $_FILES['fileAjax']['name'];
        $fileTmpName  = $_FILES['fileAjax']['tmp_name'];
        $fileType = $_FILES['fileAjax']['type'];
        $fileExtension = strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
        $ambito = htmlspecialchars($_POST["ambito"]);

        // Creo el fichero que enviaré al servidor de redmine
        $file = fopen($fileTmpName, 'r');
        $size = filesize($fileTmpName);
        $filedata = fread($file, $size);
        //
        if (isset($fileName)) {
            // Compruebo extensión del fichero subida es válido
            if (! in_array($fileExtension,$fileExtensions)) {
                $errors[] = "Las extensiones JPEG, JPG, PNG y GIF son las únicas permitidas";
            }

            //Decido a dónde se envía la imagen en función del ámbito
            $apiRedmine = $apiRedmineComun;
            $urlUploads = $urlUploadsComun;
            if( $ambito == "Vitalinux" ){
                $apiRedmine = $apiRedmineVx;
                $urlUploads = $urlUploadsVx;
            }
            //Sustituir los espacios en blanco  por guiones bajosen el nombre del fichero
            $filename2 = str_replace(" ", "_", $filename);

            fwrite($logFile, "\n".date("d/m/Y H:i:s")." upload.php: nombre del fichero modificado -- ".$filename2);
            $urlUploads = $urlUploads . $fileName2;
            // 
            
            if (empty($errors)) {
                // Hago lo relativo a redmine
                
                 $url = "https://soportearagon.catedu.es/uploads.json?filename=" . $fileName2;
                $curl = curl_init();
                // Cabeceras
                curl_setopt($curl, CURLOPT_HTTPHEADER, 
                    array(
                            'Content-Type: application/octet-stream',
                            'X-Redmine-API-Key: ' . $apiRedmine
                    )
                );
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $filedata );
                curl_setopt($curl, CURLOPT_URL, $urlUploads);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                
                fwrite($logFile, "\n".date("d/m/Y H:i:s").": Antes de ejecución de curl_exec");
                
                $result = curl_exec($curl)  ;
                
                $respuesta = json_decode($result, true);

                fwrite($logFile, "\n".date("d/m/Y H:i:s")." upload.php: respuesta".print_r($respuesta,true));
                $token = $respuesta["upload"]["token"];

                curl_close($curl);
                //Devuelvo el token
                echo $token;
            } 
        }
    }

?>
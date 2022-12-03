<?php
session_start();
require_once('secret.php');
require_once("../../../wp-load.php");

$logFile = fopen("log.txt", 'a');

$captchaCorrecto = FALSE;
if (isset($_POST['captcha_challenge']) && isset($_SESSION['captcha_text']) && $_POST['captcha_challenge'] == $_SESSION['captcha_text']) {
    $captchaCorrecto = TRUE;
} else {
    $captchaExpired = false;
    if(!isset($_SESSION['captcha_text'])){
        $captchaExpired = true;
    }
    $captchaCorrecto = FALSE;
}

fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: captcha correcto -- " . $captchaCorrecto);

// Inicializo variables
$userRedmine = "";
$passRedmine = "";
$apiRedmine = "";
$urlRedmine = "";
$projectId = "";
$ownerEmailId = "1";

//////////////////////////////
// Funciones
//////////////////////////////
function getIPAddress()
{
    //whether ip is from the share internet  
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    //whether ip is from the proxy  
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    //whether ip is from the remote address  
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * Función para añadir filtro para indicar a wp_mail que el email es de contenido HTML
 */
function set_html_content_type()
{
    return 'text/html';
}

/**
 * Configura el servidor SMTP para enviar emails con wp_mail()
 */
function configure_smtp($phpmailer)
{
    $phpmailer->isSMTP(); //switch to smtp
    $phpmailer->Host = $GLOBALS["SMTPserver"];
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = $GLOBALS["SMTPport"];
    $phpmailer->Username = $GLOBALS["SMTPsender"];
    $phpmailer->Password = $GLOBALS["SMTPpassword"];
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->From = $GLOBALS["SMTPsender"];
    $phpmailer->FromName = $GLOBALS["SMTPsenderName"];
}

/**
 * Si no existe crea la función para depurar el envío de correo
 */
if (!function_exists('debug_wpmail')) {

    function debug_wpmail($result = false)
    {
        if ($result)
            return;
        global $ts_mail_errors, $phpmailer;
        if (!isset($ts_mail_errors))
            $ts_mail_errors = array();
        if (isset($phpmailer))
            $ts_mail_errors[] = $phpmailer->ErrorInfo;
        global $logFile;
        fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: error en function_exist -- " . $ts_mail_errors);
    }
}

/**
 * Asigna la incidencia a quién corresponda en función del ámbito
 * PERO también asigna
 * - usuario de redmine
 * - pass de redmine
 * - API de redmine
 * - url de redmine
 * - projectid
 * En función del ámbito
 */
function asignarIncidenciaA($ambito)
{
    // Le indico a la función que estas variables son las de fuera
    global $userRedmine, $passRedmine, $apiRedmine, $urlRedmine, $projectId, $ownerEmailId;
    // Asigno lo común
    $userRedmine = $GLOBALS["userRedmineComun"];
    $passRedmine = $GLOBALS["passRedmineComun"];
    $apiRedmine = $GLOBALS["apiRedmineComun"];
    $urlRedmine = $GLOBALS["urlRedmineComun"];
    $projectId = "9"; //CATEDU
    // Personalizo en función de cada caso
    switch ($ambito) {
        case "Aeducar":
            $projectId = "10";
            return $GLOBALS["idCategoryAeducar"];
            break;
        case "Aramoodle":
            return $GLOBALS["idCategoryAramoodle"];
            break;
        case "Aularagón":
            return $GLOBALS["idCategoryAularagon"];
            break;
        case "Competencias digitales":
            $projectId = "13";
            return $GLOBALS["idCategoryCDD"];
            break;
        case "Doceo":
            return $GLOBALS["idCategoryDoceo"];
            break;
        case "FP Distancia":
            $projectId = "12";
            return $GLOBALS["idCategoryFP"];
            break;
        case "STEAM":
            return $GLOBALS["idCategorySTEAM"];
            break;
        case "Vitalinux":
            $userRedmine = $GLOBALS["userRedmineVx"];
            $passRedmine = $GLOBALS["passRedmineVx"];
            $apiRedmine = $GLOBALS["apiRedmineVx"];
            $urlRedmine = $GLOBALS["urlRedmineVx"];
            $projectId = "2";
            $ownerEmailId = "4";
            return $GLOBALS["idCategoryVitalinux"];
            break;
        case "WordPress":
            return $GLOBALS["idCategoryWordPress"];
            break;
    }
    return ["idCategoryOtros"];
}
//////////////////////////////
// Recojo parámetros del form
//////////////////////////////

$ambito = htmlspecialchars($_POST["ambito"]);
$asunto = htmlspecialchars($_POST["asunto"]);
$nombre_solicitante = htmlspecialchars($_POST["nombre_solicitante"]);
$pape_solicitante = htmlspecialchars($_POST["pape_solicitante"]);
$sape_solicitante = htmlspecialchars($_POST["sape_solicitante"]);
$email_solicitante = htmlspecialchars($_POST["email_solicitante"]);
fwrite($logFile, "\n" . date("d/m/Y H:i:s") . ": email solicitante -- " . $email_solicitante);
$otros = htmlspecialchars($_POST["otros"]);
//
$captcha = htmlspecialchars($_POST["captcha"]);
$token = htmlspecialchars($_POST["token"]);
$adjuntwo = htmlspecialchars($_POST["adjunto"]);

fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: parametros del form recogidos");
fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: " . print_r($_POST, true));

//////////////////////////////
// Antes de procesar miro si campos obligatorios están rellenos para evitar envío masivo de navegadores que se saltan required
//////////////////////////////

$camposObligatoriosRellenos = true;
if ($nombre_solicitante == "" || $pape_solicitante == "" || $email_solicitante == "") {
    $camposObligatoriosRellenos = false;
}


if ($camposObligatoriosRellenos && $captchaCorrecto) {
    //////////////////////////////
    // Creo variables iniciales
    //////////////////////////////
    $date = date('d-m-Y H:i:s');
    $ip = getIPAddress();
    fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: dirección IP -- " . $ip);

    $descriptionRedmine = '*' . $nombre_solicitante . ' ' . $pape_solicitante . '* ha enviado el ' . $date . ' desde la IP ' . $ip . ' una incidencia con la siguiente información: &#xD;';
    $descriptionRedmine .= '&#xD;';
    $descriptionRedmine .= '- *Ámbito* : ' . $ambito . '&#xD;';
    $descriptionRedmine .= '- *Asunto* : ' . $asunto . '&#xD;';
    $descriptionRedmine .= '- *Nombre solicitante* : ' . $nombre_solicitante . '&#xD;';
    $descriptionRedmine .= '- *1er apellido solicitante* : ' . $pape_solicitante . '&#xD;';
    $descriptionRedmine .= '- *2º apellido solicitante* : ' . $sape_solicitante . '&#xD;';
    $descriptionRedmine .= '- *E-mail solicitante* : ' . $email_solicitante . '&#xD;';
    $descriptionRedmine .= '- *Explicación de la situación* : ' . $otros . '&#xD;';
   
    //////////////////////////////
    // Contacto con RedMine para crear la incidencia
    //////////////////////////////
    fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: URL " . $url);

    $asignarA = asignarIncidenciaA($ambito);
    fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: incidencia asignada a " . $asignarA);

    $curl = curl_init();

    $res_curl = curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));

    $res_curl = curl_setopt($curl, CURLOPT_POST, 1);

    $issue =  '
        <?xml version="1.0"?>
        <issue>
        <project_id>' . $projectId . '</project_id>
        <subject>' . $asunto . ' (' . $ambito . ')</subject>';

    if ($token != "") {
        $issue .= '
            <uploads type="array">
              <upload>
                <token>' . $token . '</token>
                <filename>' . $adjunto . '</filename>
                <description>Fichero adjunto</description>
                <content_type>image/png</content_type>
              </upload>
            </uploads>';
    }

    $issue .= '<description><![CDATA[' . $descriptionRedmine . ']]></description>
        <priority_id>2</priority_id>
        <custom_fields type="array">
            <custom_field id="'.$ownerEmailId.'" name="owner-email">
                <value>' . $email_solicitante . '</value>
            </custom_field>
        </custom_fields>
        <category_id>' . $asignarA . '</category_id>
        </issue>';
    
    fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: issue -- ".$issue);
    $res_curl = curl_setopt($curl, CURLOPT_POSTFIELDS, $issue);

    $res_curl = curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $res_curl = curl_setopt($curl, CURLOPT_USERPWD, $userRedmine . ":" . $passRedmine);

    $res_curl = curl_setopt($curl, CURLOPT_URL, $urlRedmine);

    $res_curl = curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: result -- ".var_export(json_decode($result,true),true));
    if($result === false){
        fwrite($logFile, "\n" . date("d/m/Y H:i:s") ." accion.php : Curl error .. " . curl_error($curl));
    }
    curl_close($curl);
    
    $respuesta = json_decode($result, true);

    $incidenciaCreada = $respuesta["issue"];
    $incidenciaCreadaId = $incidenciaCreada["id"];
    
    $exitoCreandoIncidencia = false;
    if (isset($incidenciaCreadaId) && $incidenciaCreadaId !== '') {
        $exitoCreandoIncidencia = true;
    }
    //BORRAR
    //$exitoCreandoIncidencia = true;
    //$incidenciaCreadaId = 1;
    //BORRAR*************************/
    
    //////////////////////////////
    // Envío email al usuario con copia de su solicitud original
    //////////////////////////////
    if ($exitoCreandoIncidencia) {
        //FILTRO Y ACCIÓN PARA QUE FUNCIONE EL ENVÍO DE EMAILES EN WORDPRESS//
        add_filter('wp_mail_content_type', 'set_html_content_type');

        add_action('phpmailer_init', 'configure_smtp');
        //FILTRO Y ACCIÓN PARA QUE FUNCIONE EL ENVÍO DE EMAILES EN WORDPRESS//

        $subject = 'Nueva incidencia - CATEDU';

        $cuerpo = 'Hola ' . $nombre_solicitante . ',<br/>';
        $cuerpo .= 'su incidencia realizada el ' . $date . ' ha sido recogida en nuestro sistema con el id <strong>' . $incidenciaCreadaId . '</strong>. La misma contiene la siguiente información:<br/>';
        $cuerpo .= '<ul>';
        $cuerpo .= '<li><b>Ámbito</b>: ' . $ambito . '</li>';
        $cuerpo .= '<li><b>Asunto</b>: ' . $asunto . '</li>';
        $cuerpo .= '<li><b>Nombre solicitante</b>: ' . $nombre_solicitante . '</li>';
        $cuerpo .= '<li><b>1er apellido solicitante</b>: ' . $pape_solicitante . '</li>';
        $cuerpo .= '<li><b>2º apellido solicitante</b>: ' . $sape_solicitante . '</li>';
        $cuerpo .= '<li><b>E-mail solicitante</b>: ' . $email_solicitante . '</li>';
        $cuerpo .= '<li><b>Explicación de la situación</b>: ' . $otros . '</li>';
        $cuerpo .= '</ul>';
        $cuerpo .= 'No conteste a este correo electrónico puesto que se trata de una cuenta desatendida y automatizada<br/>';
        $cuerpo .= 'Saludos<br/><br/>';
        $cuerpo .= 'CATEDU';

        $exitoEnviandoEmail = wp_mail($email_solicitante, $subject, $cuerpo);
        if ($exitoEnviandoEmail) {
            fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: email enviado correctamente");
        } else {
            fwrite($logFile, "\n" . date("d/m/Y H:i:s") . "accion.php: error enviando email");
            //debug_wpmail($exitoEnviandoEmail);
        }

        /**FILTRO Y ACCIÓN ELIMINADO PARA QUE NO INTERFIERA CON EL RESTO DE LA WEB EN WORDPRESS**/
        remove_filter('wp_mail_content_type', 'set_html_content_type');
        remove_action('phpmailer_init', 'configure_smtp');
        /**FILTRO Y ACCIÓN ELIMINADO PARA QUE NO INTERFIERA CON EL RESTO DE LA WEB EN WORDPRESS**/
    }
}

//////////////////////////////
// comprobaciones para informar a los usuarios del éxito/fallo de su comunicación
//////////////////////////////
$error = '';
$exito = '';
$html = '';
$advertencia = '';

fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: camposObligatoriosRellenos -- ".$camposObligatoriosRellenos."; captchaCorrecto -- ".$captchaCorrecto."; exitoCreandoIncidencia -- ".$exitoCreandoIncidencia."; exitoEnvindoEmail -- ".$exitoEnviandoEmail);

if (!$camposObligatoriosRellenos) {
    $error =  'Debe rellenar todos los campos obligatorios. Incidencia NO procesada.<br/> Vuelva a intentarlo, por favor.';
} elseif (!$captchaCorrecto) {
    $error =  'El código de captcha no es correcto. Incidencia NO procesada.<br/> Vuelva a intentarlo, por favor.';
    if($captchaExpired){
        $error = 'El código de captcha ha caducado, genere otro y vuelva a intentarlo, por favor. Incidencia NO procesada.';
    }
} elseif ($exitoCreandoIncidencia && $exitoEnviandoEmail) {
    $exito =  'Incidencia ' . $incidenciaCreadaId . ' creada. Se le ha enviado un email con copia de la misma.';
} elseif ($exitoCreandoIncidencia && !$exitoEnviandoEmail) {
    $exito =  'Incidencia ' . $incidenciaCreadaId . ' creada pero HA FALLADO EL ENVÍO DEL EMAIL A SU CUENTA CON COPIA DE LA MISMA. NO se le podrá comunicar la resolución de la misma o realizar consultas adicionales.';
} else {
    $error =  'Ha fallado la creación de la incidencia. Vuelva a intentarlo, por favor.';
}

if ($error != "" || !$exitoCreandoIncidencia || !$exitoEnviandoEmail) {
    $advertencia = 'Si el error persiste puede enviar la incidencia por correo a las siguientes direcciones:
                <ul>
                    <li><a href=\'mailto:soportecatedu@educa.aragon.es\'>soportecatedu@educa.aragon.es</a> si es una incidencia general sobre Catedu o Doceo
                    </li>
                    <li><a href=\'mailto:soporteaeducar@educa.aragon.es\'>soporteaeducar@educa.aragon.es</a> si es una incidencia sobre la plataforma AeducAR
                    </li>
                    <li><a href=\'mailto:soportevitalinux@educa.aragon.es\'>soportevitalinux@educa.aragon.es</a> si es una incidencia sobre Vitalinux. El programa vitalinux tiene su propia web de soporte abierta en el <a href=\'https://soporte.vitalinux.educa.aragon.es\'>siguiente sitio</a></li>
                </ul>';
}

fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: Error -- " . $error);
fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: Exito -- " . $exito);

$ambito_html = htmlentities($ambito, ENT_QUOTES, "UTF-8");
$asunto_html = htmlentities($asunto, ENT_QUOTES, "UTF-8");
$nombre_solicitante_html = htmlentities($nombre_solicitante, ENT_QUOTES, "UTF-8");
$pape_solicitante_html = htmlentities($pape_solicitante, ENT_QUOTES, "UTF-8");
$sape_solicitante_html = htmlentities($sape_solicitante, ENT_QUOTES, "UTF-8");
$email_solicitante_html = htmlentities($email_solicitante, ENT_QUOTES, "UTF-8");
$otros_html = htmlentities($otros, ENT_QUOTES, "UTF-8");

if ($exitoCreandoIncidencia) {
    $html = '<h3>INCIDENCIA CREADA Y ENVIADA CORRECTAMENTE</h3>
                <p>La informacion recogida es la siguiente:</p>
                <div class=\'col-12\'>
                    <ul><li>Ambito: ' . $ambito_html . '</li>
                        <li>Asunto: ' . $asunto_html . '</li>
                        <li>Nombre solicitante: ' . $nombre_solicitante_html . '</li>
                        <li>1er apellido solicitante: ' . $pape_solicitante_html . '</li>
                        <li>2º apellido solicitante: ' . $sape_solicitante_html . '</li>
                        <li>E-mail solicitante: ' . $email_solicitante_html .'</li>
                        <li>Explicación de la situación: ' . $otros_html . '</li>
                    </ul>
                </div>';
}

fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: html -- " . $html);
fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: advertencia -- " . $advertencia);

/*$error = 'Error creando incidencia';
$exito = '';
$exitoCreandoIncidencia = null;
$html = '';
$advertencia = 'Advertencia';*/

fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: exito -- " . $exito);


$resultado = array("error" => $error, "exito" => $exito, "exitoCreandoIncidencia" => $exitoCreandoIncidencia, "html" => $html, "advertencia" => $advertencia);

//$resultado = ["error" => $error, "exito" => $exito, "exitoCreandoIncidencia" => $exitoCreandoIncidencia, "html" => $html, "advertencia" => $advertencia];
//$resultado = array('error' => '');


//fwrite($logFile, "\n".date("d/m/Y H:i:s")." accion.php: array -- ". print_r($resultado,true));
fwrite($logFile, "\n" . date("d/m/Y H:i:s") . " accion.php: json -- " . json_encode($resultado));
echo json_encode($resultado);
//echo json_encode(array("error" => $error, "exito" => $exito, "exitoCreandoIncidencia" => $exitoCreandoIncidencia, "html" => $html, "advertencia" => $advertencia));

<?php
defined('ABSPATH') or die("Debes acceder desde el menú de navegación");
if (!current_user_can('administrator')) {
    wp_die(__('No tienes suficientes permisos para acceder a esta página.', 'redmine-contact'));
} else {
    wp_enqueue_script('jquery-script');
    wp_enqueue_script('contact-redmine-js');
    wp_enqueue_script("bootstrap-script");
    wp_enqueue_style('bootstrap-css');
    wp_enqueue_style('contact-redmine-css');
    $translation_array = array('pluginsURL' => plugins_url()); //after wp_enqueue_script
    wp_localize_script('contact-redmine-js', 'jsVars', $translation_array);
?>
    <div id="mentorias_cdd" class="wrap">
        <h1 class="wp-heading-inline">Soporte CATEDU</h1>
        <div class="wrap">
            <p>Al activar este plugin se crea una página llamada <b>Soporte Catedu</b>, que incluye un formulario para enviar incidencias al redmine de soporte de CATEDU</p>
            <p>ES IMPORTANTE NO ENTRAR A EDITAR ESTA PÁGINA.</p>
            <p>Si se entra a editar se desconfigura todo el código.</p>
            <h2 class="title">Qué hacer si se desconfigura la página</h2>
            <p>Si se desconfigura la página hay que:</p>
            <ol>
                <li>Desactivar el plugin</li>
                <li>Activar el plugin</li>
                <li>Volver a enlazar la página al menú correspondiente</li>
            </ol>
        </div>
    </div>

<?php }?>
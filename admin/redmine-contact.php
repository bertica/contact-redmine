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
            <p>Al activar este plugin se crea una página llamada Soporte Catedu, que incluye un formulario para enviar incidencias al redmine de soporte de CATEDU</p>
            <p>Es importante no modificar esta página</p>
        </div>
    </div>

<?php }?>
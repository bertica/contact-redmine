<?php
/*
Plugin Name: Contact Redmine
Plugin URI:
Description: Abre incidencias en la plataforma de soporte de CATEDU asignándola directamente a la persona indicada.
Version: 1.0.0
Author: Berta Besteiro
Author URI: 
License: GPLv2 or later
Text Domain: contact-redmine
*/
require_once('secret.php');

defined('ABSPATH') or die("Debes acceder desde el menú de navegación");
define('PLUGIN_SOPORTE_CATEDU_DIR', plugin_dir_path(__FILE__));

function redmine_tool_install()
{
}

function redmine_register_css()
{
    wp_register_style('bootstrap-css', '/wp-content/plugins/contact-redmine/admin/css/bootstrap.min.css');
    //wp_register_style( 'bootstrap-css', '/wp-content/plugins/contact-redmine/admin/css/bootstrap.min.css', array( 'contact-redmine-fe-css' ), false, 'all' );
    wp_register_style('contact-redmine-css', '/wp-content/plugins/contact-redmine/admin/css/contact-redmine.css');
    //wp_register_style('contact-redmine-fe-css', '/wp-content/plugins/contact-redmine/css/contact-redmine-fe.css',array('bootstrap-css'),false,'all');
    wp_register_style('contact-redmine-fe-css', '/wp-content/plugins/contact-redmine/css/contact-redmine-fe.css');
    wp_register_style('font-googleapis', 'https://fonts.googleapis.com/css?family=Poppins');
}


function redmine_enqueue_css()
{
    wp_enqueue_style("contact-redmine-fe-css");
    //wp_enqueue_style('bootstrap-css');
    //wp_enqueue_style('contact-redmine-css');
    wp_enqueue_style('font-googleapis');
}


function redmine_register_js()
{
    wp_register_script('bootstrap-script', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.min.js');
    wp_register_script('contact-redmine-js', '/wp-content/plugins/contact-redmine/admin/js/contact-redmine.js');
    wp_register_script('contact-redmine-fe-js', '/wp-content/plugins/contact-redmine/js/contact-redmine-fe.js');
    wp_register_script('jquery-script', 'https://code.jquery.com/jquery-3.6.0.min.js');
}

function redmine_enqueue_js()
{
    wp_enqueue_script("jquery-script");
    wp_enqueue_script("contact-redmine-fe-js");
    $translation_array = array('pluginsURL' => plugins_url());
    wp_localize_script('contact-redmine-fe-js', 'jsVars', $translation_array);
}

add_action('init', 'redmine_register_css');
add_action('init', 'redmine_register_js');
add_action('init', 'redmine_enqueue_css');
add_action('init', 'redmine_enqueue_js');


//Para hacer uso de sesiones para el captcha del formulario
add_action('init', 'rm_session_start');
function rm_session_start()
{
    if (!session_id()) {
        //seteo la vida de la session en 60 segundos    
        //ini_set("session.cookie_lifetime", "60");
        //seteo el maximo tiempo de vida de la session
        //ini_set("session.gc_maxlifetime", "60");
        session_start(); // Iniciamos la sesion
    }
}


function redmine_menu_administrador()
{
    add_menu_page('Integración Redmine', 'Integración Redmine', 'manage_options', 'contact-redmine', 'load_redmine_contact', '', 26);
}

function load_redmine_contact()
{
    include(PLUGIN_SOPORTE_CATEDU_DIR . 'admin/redmine-contact.php');
}

add_action('admin_menu', 'redmine_menu_administrador');



function add_my_custom_page()
{
    // Create post object

    $form = '
    <div class="row">
        <div class="col-12">
            <div id="resultado-envio" class="oculto"></div>
            <div id="mensaje-error" class="alert alert-danger oculto" role="alert">
            </div>
            <div id="mensaje-exito" class="alert alert-success oculto" role="alert">
            </div>
            <div id="mensaje-advertencia" class="alert alert-warning oculto" role="alert">
            </div>
            <button id="volver" class="button btn btn-primary oculto" value="Volver">Enviar otra incidencia</button>
            <form id="form_soporte" name="form_soporte" >
                <div class="alert alert-warning" role="alert">
                    Asegúrese de introducir el correo electrónico correctamente
                </div>
                
                <div class="mb-3">
                    <label for="ambito-select" class="form-label">(*) Ámbito</label>
                    <select id="ambito-select" name="ambito-select" class="form-select" required >
                        <option value="">Elija una opción</option>
                        <option value="Aeducar">Aeducar</option>
                        <option value="Aramoodle">Aramoodle</option>
                        <option value="Aularagón">Aularagón</option>
                        <option value="Competencias digitales">Competencias digitales</option>
                        <option value="Doceo">Doceo</option>
                        <option value="FP Distancia">FP Distancia</option>
                        <option value="STEAM">STEAM</option>
                        <option value="Vitalinux">Vitalinux</option>
                        <option value="WordPress">WordPress</option>
                        <option value="otro">Otro ámbito o Desconozco el ámbito</option>
                    </select>
                    <input type="hidden" id="ambito" name="ambito" value="" />
                </div>

                <div class="mb-3">
                    <label for="asunto" class="form-label">(*) Asunto</label>
                    <input type="text" class="form-control" id="asunto" name="asunto" required >
                </div>

                <div class="mb-3">
                    <label for="nombre_solicitante" class="form-label">(*) Su nombre</label>
                    <input type="text" class="form-control" id="nombre_solicitante" name="nombre_solicitante" required >
                </div>

                <div class="mb-3">
                    <label for="pape_solicitante" class="form-label">(*) Su 1er apellido</label>
                    <input type="text" class="form-control" id="pape_solicitante" name="pape_solicitante" required >
                </div>

                <div class="mb-3">
                    <label for="sape_solicitante" class="form-label">Su 2º apellido</label>
                    <input type="text" class="form-control" id="sape_solicitante" name="sape_solicitante" >
                </div>

                <div class="mb-3">
                    <label for="email_solicitante" class="form-label">(*) Su email</label>
                    <input type="email" class="form-control" id="email_solicitante" name="email_solicitante" required >
                </div>
                <div id="error-imagen" class="alert alert-danger oculto mb-3" role="alert"></div>
                <div class="mb-3">
                    <label for="adjunto" class="form-label">Adjunte una imagen si lo desea</label>
                    <input type="file" class="form-control" id="adjunto" disabled >
                    <input type="hidden" id="token" name="token" value="" />
                </div>

                <div class="mb-3">
                    <label for="otros" class="form-label">(*) Explique su incidencia</label>
                    <textarea required rows="8" cols="60" id="otros" name="otros" spellcheck="true" class="form-control text-ltr" required ></textarea>
                </div>

                <div class="mb-3">
                    <img src="" alt="CAPTCHA" class="captcha-image">
                    <p>¿No puedes leer la imagen? <a id="refresh-captcha">click aquí</a> para refrescar</p>
                </div>

                <div class="mb-3">
                    <label for="captcha_challenge" class="form-label">(*) Captcha</label>
                    <input type="text" class="form-control" id="captcha_challenge" name="captcha_challenge" pattern="[A-Z]{6}" disabled required >
                </div>
                <div id="sending"></div>
                <button id="boton_enviar" type="submit" class="button btn btn-primary" value="Enviar">Enviar</button>
            </form>
        </div>
    </div>';

    $soporte_page = array(
        'post_title'    => wp_strip_all_tags('Soporte CATEDU'),
        'post_content'  => $form,
        'post_status'   => 'publish',
        'post_author'   => 1,
        'post_type'     => 'page',
    );

    // Insert the post into the database
    wp_insert_post($soporte_page);
}

register_activation_hook(__FILE__, 'add_my_custom_page');

function deactivate_plugin()
{

    $page_id = get_page_by_title('Soporte CATEDU');
    wp_delete_post($page_id->ID, true);
}
register_deactivation_hook(__FILE__, 'deactivate_plugin');

$(document).ready(function () {
    //Eventos
    $("#ambito-select").on("change", function () {
        ambito = $("#ambito-select").val();
        $("#ambito").val(ambito);
        if (ambito != "") {
            $("#adjunto").prop("disabled", false);
        } else {
            $("#adjunto").prop("disabled", true);
        }
        $("#ambito-select").prop("disabled", true);
    });

    //Refresh Captcha
    function refreshCaptcha() {
        $now = Date.now().toString();
        $(".captcha-image").attr("src", jsVars.pluginsURL + '/contact-redmine/captcha.php?' + $now);
        $("#captcha_challenge").prop("disabled", false);
    }

    $("a#refresh-captcha").on("click", function () {
        refreshCaptcha();
    });

    //Fichero adjunto
    $("#adjunto").on("change", function (e) {
        $("#error-imagen").html("");
        $("#error-imagen").addClass("oculto");
        /*myFile = $("#adjunto");
        alert(myFile.files);*/
        ambito = $("#ambito-select").val();
        //files = myFile.files;
        formData = new FormData();
        //var file = files[0];
        file = e.target.files[0];

        // Check the file type
        if (!file.type.match('image.*')) {
            mensajeError = "El archivo seleccionado no es una imagen";
            $("#error-imagen").html(mensajeError);
            $("#error-imagen").removeClass("oculto");
            $("#adjunto").val(null);
            return;
        }
        //
        formData.append('fileAjax', file, file.name);
        formData.append('ambito', ambito);

        // Set up the request
        var xhr = new XMLHttpRequest();

        // Open the connection
        xhr.open('POST', jsVars.pluginsURL + '/contact-redmine/upload.php', true);

        // Set up a handler for when the task for the request is complete
        xhr.onload = function () {
            if (xhr.status == 200) {
                //statusP.innerHTML = 'Upload copmlete!';
                console.log("respuesta: " + xhr.responseText);
                $("#token").val(xhr.responseText);
            } else {
                //statusP.innerHTML = 'Upload error. Try again.';
                console.log("Error: " + xhr.responseText);
            }
        };

        // Send the data.
        xhr.send(formData);

    });

    $("form#form_soporte").submit(function (e) {
       
        e.preventDefault(); //form will not submitted
        
        
        if(!$("#mensaje-error").hasClass("oculto")) {
            $("#mensaje-error").addClass("oculto");
        }
        $("#mensaje-error").html("");
        if(!$("#mensaje-envio").hasClass("oculto")) {
            $("#resultado-envio").addClass("oculto");
        }
        $("#resultado-envio").html("");
        if(!$("#mensaje-exito").hasClass("oculto")) {
            $("#mensaje-exito").addClass("oculto");
        }
        $("#mensaje-exito").html("");
        if(!$("#mensaje-advertencia").hasClass("oculto")) {
            $("#mensaje-advertencia").addClass("oculto");
        }
        $("#mensaje-advertencia").html("");
        $.ajax({
            url: jsVars.pluginsURL + "/contact-redmine/accion.php",
            method: "POST",
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            dataType: "json",
            beforeSend : function() { 
                $("#boton_enviar").addClass("oculto");
                $('#sending').append("<img src='"+jsVars.pluginsURL + "/contact-redmine/img/sending.gif' id='loader'>");
            }
        }).done(function (data) {
            //console.log(data);
            error = data.error;
            exito = data.exito;
            exitoCreandoIncidencia = data.exitoCreandoIncidencia;
            html = data.html;
            advertencia = data.advertencia;
            if (error != "") {
                $("#mensaje-error").html(error);
                $("#mensaje-error").removeClass("oculto");
                /*if(!$("#mensaje-exito").hasClass("oculto")){
                    $("#mensaje-exito").addClass("oculto");
                }*/
                var target = $('#resultado-envio');
                if (target.length) {
                    $('html,body').animate({
                        scrollTop: target.offset().top
                    }, 1000);
                    //return false;
                }
                //refreshCaptcha();
            } else if (exito != '') {
                $("#mensaje-exito").html(exito);
                $("#mensaje-exito").removeClass("oculto");
                /*if(!$("#mensaje-error").hasClass("oculto")){
                    $("#mensaje-error").addClass("oculto");
                }*/
                $("form#form_soporte").addClass("oculto");
                $("button#volver").removeClass("oculto");
            }
            if (exitoCreandoIncidencia) {
                $("#resultado-envio").html(html);
                $("#resultado-envio").removeClass("oculto");
                $("form#form_soporte")[0].reset();
            }
            if(advertencia != ''){
                 $("#mensaje-advertencia").html(advertencia);
                 $("#mensaje-advertencia").removeClass("oculto");
            }
            $('#loader').remove();
            $("#boton_enviar").removeClass("oculto");
        }).fail(function (jqXHR, textStatus, ErrorThrown) {
            //console.log(ErrorThrown);
            if (jqXHR.status === 0) {
                console.log('No conecta: Verificar la red.');
            } else if (jqXHR.status == 404) {
                console.log('Requested page not found [404]');
            } else if (jqXHR.status == 500) {
                console.log('Internal Server Error [500].');
            } else if (textStatus === 'parsererror') {
                console.log('Requested JSON parse failed.');
            } else if (textStatus === 'timeout') {
                console.log('Time out error.');
            } else if (textStatus === 'abort') {
                console.log('Ajax request aborted.');
            } else {
                console.log('Uncaught Error: ' + jqXHR.responseText);
            }
        });
    });

    $("button#volver").on("click", function (e) {
        if(!$("#mensaje-error").hasClass("oculto")) {
            $("#mensaje-error").addClass("oculto");
        }
        $("#mensaje-error").html("");
        if(!$("#mensaje-envio").hasClass("oculto")) {
            $("#resultado-envio").addClass("oculto");
        }
        $("#resultado-envio").html("");
        if(!$("#mensaje-exito").hasClass("oculto")) {
            $("#mensaje-exito").addClass("oculto");
        }
        $("#mensaje-exito").html("");
        if(!$("#mensaje-advertencia").hasClass("oculto")) {
            $("#mensaje-advertencia").addClass("oculto");
        }
        $("#mensaje-advertencia").html("");
        $("form#form_soporte")[0].reset();
        $("form#form_soporte").removeClass("oculto");
        $("button#volver").addClass("oculto");
        $(".captcha-image").attr("src","");
        $("#ambito-select").prop("disabled", false);
    });
});
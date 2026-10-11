/*
   Clínica Dental Sonrisa — comportamiento de la demostración.

   Propio y sin dependencias, como el CSS: la plantilla no comparte nada con el
   sitio corporativo ni con el catálogo. Hace dos cosas y ninguna es crítica:

   1. Abre y cierra el menú móvil (con el botón, con Escape y al elegir un
      enlace, que es lo que se espera en una página de desplazamiento por anclas).
   2. Escribe el año actual en el pie, para no tener que actualizarlo a mano.

   Sin JavaScript la página sigue siendo usable: el menú de escritorio está
   siempre visible por CSS y el desplazamiento suave está en la hoja de estilos.
*/
(function () {
    'use strict';

    var boton = document.getElementById('menu-boton');
    var menu = document.getElementById('menu-principal');

    if (boton && menu) {
        var abrir = function (estado) {
            menu.classList.toggle('esta-abierto', estado);
            boton.setAttribute('aria-expanded', estado ? 'true' : 'false');
        };

        boton.addEventListener('click', function () {
            abrir(!menu.classList.contains('esta-abierto'));
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                abrir(false);
            }
        });

        menu.querySelectorAll('a').forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                abrir(false);
            });
        });
    }

    var anio = document.getElementById('anio');

    if (anio) {
        anio.textContent = String(new Date().getFullYear());
    }
})();

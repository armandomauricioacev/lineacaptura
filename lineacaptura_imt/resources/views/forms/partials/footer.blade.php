</div>
  </main>

  @php
    // PRODUCCIÓN v3 (oficial)
    $GOB_JS = 'https://framework-gb.cdn.gob.mx/gm/v3/assets/js/gobmx.js';
  @endphp

  {{-- JS oficial GOB.MX (PROD v3) --}}
  <script src="{{ $GOB_JS }}"></script>

  {{-- Scripts por-vista --}}
  @stack('scripts')

  <script>
    // Opcional: inicializaciones seguras (solo si $gmx existe)
    if (window.$gmx) {
      $gmx(function () {
        // …
      });
    }

    // === INICIO DE LA MODIFICACIÓN ===
    // Prevenir navegación con botones del navegador y redirigir a inicio
    (function() {
      // Solo ejecuta este script si la página actual NO es la de inicio.
      // Así evitamos bucles de redirección.
      if (window.location.pathname !== '/inicio' && window.location.pathname !== '/') {
        
        if (window.history && window.history.pushState) {
          // Agrega una entrada en el historial. Esto es clave para que el evento 'popstate' se active
          // la primera vez que el usuario presiona "atrás".
          window.history.pushState(null, document.title, window.location.href);
          
          // Escuchamos el evento 'popstate', que se dispara cuando el usuario navega
          // con los botones de atrás/adelante del navegador.
          window.addEventListener('popstate', function(event) {
            // En lugar de la alerta, redirigimos a la página de inicio.
            // Usamos location.replace() para que esta acción no quede en el historial,
            // evitando que el usuario pueda usar "adelante" para volver.
            window.location.replace("{{ route('inicio') }}");
          });
        }
      }
    })();
    // === FIN DE LA MODIFICACIÓN ===
  </script>
</body>
</html>
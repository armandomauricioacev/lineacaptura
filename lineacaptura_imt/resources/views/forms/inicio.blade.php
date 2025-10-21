@extends('layouts.base')

@section('title', 'PAGA IMT')

@section('content')
<br>

<style>
  .dep-link {
    background: none;
    border: 0;
    padding: 0;
    margin: 0;
    cursor: pointer;
    color: #007bff; /* Azul más fuerte y visible */
    text-decoration: underline;
    -webkit-tap-highlight-color: transparent; /* quita flash en móviles */
    transition: color 0.2s ease; /* Transición suave para el cambio de color */
  }

  /* Apaga cualquier contorno/halo al enfocarse o activarse */
  .dep-link:focus,
  .dep-link:active,
  .dep-link:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    border: 0 !important;
  }

  .dep-link::-moz-focus-inner { border: 0; } /* Firefox */

  /* Si algo del theme mete sombras/outline, lo neutralizamos dentro del panel */
  .panel-body .dep-link,
  .panel-body .dep-link:focus,
  .panel-body .dep-link:active,
  .panel-body .dep-link:focus-visible {
    outline: none !important;
    box-shadow: none !important;
    border: 0 !important;
  }
</style>

  {{-- LOGOS: centrados en XS, en extremos desde SM+ --}}
  <div class="row" style="margin-top:15px; margin-bottom:10px">
    {{-- Izquierda --}}
    <div class="col-xs-12 col-sm-6">
      {{-- Móvil (XS): centrado --}}
      <div class="visible-xs-block text-center">
        <img
          src="{{ asset('img/comunicaciones.png') }}"
          alt="Secretaría de Infraestructura, Comunicaciones y Transportes"
          class="img-responsive"
          style="display:inline-block; max-height:56px">
      </div>
      {{-- Tablet/Escritorio (SM+): alineado a la izquierda --}}
      <div class="hidden-xs">
        <img
          src="{{ asset('img/comunicaciones.png') }}"
          alt="Secretaría de Infraestructura, Comunicaciones y Transportes"
          class="img-responsive"
          style="max-height:80px">
      </div>
    </div>

    {{-- Derecha --}}
    <div class="col-xs-12 col-sm-6">
      {{-- Móvil (XS): centrado --}}
      <div class="visible-xs-block text-center">
        <img
          src="{{ asset('img/imt.png') }}"
          alt="Instituto Mexicano del Transporte"
          class="img-responsive"
          style="display:inline-block; max-height:48px">
      </div>
      {{-- Tablet/Escritorio (SM+): alineado a la derecha --}}
      <div class="hidden-xs text-right">
        <img
          src="{{ asset('img/imt.png') }}"
          alt="Instituto Mexicano del Transporte"
          class="img-responsive"
          style="display:inline-block; max-height:80px">
      </div>
    </div>
  </div>
<br>

  {{-- TÍTULO: centrado en XS, alineado normal en SM+ --}}
  <div class="hidden-xs">
    <h1 style="margin:10px 0 6px;">Bienvenido a PAGA IMT</h1>
    <div style="height:4px; width:48px; background:#a57f2c; margin:6px 0 18px;"></div>
  </div>

  <div class="visible-xs-block text-center">
    <h1 style="margin:10px 0 6px;">Bienvenido a PAGA IMT</h1>
    <div style="height:4px; width:48px; background:#a57f2c; margin:6px auto 18px;"></div>
  </div>

  {{-- PANEL ÚNICO --}}
  <div class="row" style="margin-top:10px">
    <div class="col-xs-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          Secretaría de Infraestructura, Comunicaciones y Transportes
        </div>
        <div class="panel-body">
          {{-- Iteramos por las dependencias --}}
          @foreach ($dependencias as $dependencia)
            <div>
              {{-- Botón que envía el form oculto (sin JS) y se ve como link --}}
              <button type="submit"
                      class="dep-link"
                      form="dependencia-form-{{ $dependencia->id }}">
                {{ $dependencia->nombre }}
              </button>

              {{-- Formulario oculto para guardar el ID de la dependencia --}}
              <form id="dependencia-form-{{ $dependencia->id }}"
                    action="{{ route('tramite.store') }}"
                    method="POST"
                    style="display:none;">
                  @csrf
                  <input type="hidden" name="dependenciaId" value="{{ $dependencia->id }}">
              </form>
            </div>
          @endforeach
        </div>
      </div>
      <p style="margin-top:20px; color:#666; font-size:13px;">
        Selecciona la dependencia para iniciar el trámite de pago y generación de línea de captura.
      </p>
      <br>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    // Previene que se pueda volver atrás en el historial del navegador.
    history.pushState(null, document.title, location.href);
    window.addEventListener('popstate', function () {
      history.pushState(null, document.title, location.href);
    });
  })();
</script>
@endpush
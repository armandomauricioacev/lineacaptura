@extends('layouts.base')

@section('title', 'Resumen de pago')

@section('content')
  <style>
    /* ... (Tu CSS base se mantiene igual) ... */
    :root{ --gob-rojo:#611232; }
    #pasos .nav-pills{ display:flex; justify-content:center; align-items:stretch; gap:12px; padding-left:0; flex-wrap:nowrap; }
    #pasos .nav-pills>li>a{ width:220px; min-height:64px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; line-height:1.2; text-decoration:none; color:#111; background:#fff; border:1px solid #ddd; border-radius:4px; padding:8px 14px; cursor:default; pointer-events:none; }
    #pasos .nav-pills>li.active>a{ background:var(--gob-rojo); color:#fff; border-color:var(--gob-rojo); }
    #pasos .nav-pills>li.active>a small{ color:#fff; }
    .btn-gob-outline{ background:#fff !important; color:var(--gob-rojo) !important; border:2px solid var(--gob-rojo) !important; box-shadow:none !important; text-decoration:none !important; }
    .btn-gob-outline:hover, .btn-gob-outline:focus{ background:var(--gob-rojo) !important; color:#fff !important; border-color:var(--gob-rojo) !important; box-shadow:none !important; text-decoration:none !important; outline: none !important; }
    
    /* Estilos generales de la vista */
    .equal-panels { display: flex; flex-wrap: wrap; margin-bottom: 20px; }
    .equal-panels > [class*="col-"] { display: flex; padding-left: 10px; padding-right: 10px; }
    .caja { border:1px solid #e5e5e5; border-radius:6px; padding:16px; background:#fff; width: 100%; }
    .resumen.dl-horizontal dt { color: #555; font-weight: 600; width: 160px; text-align: left; white-space: normal; }
    .resumen.dl-horizontal dd { margin-left: 180px; margin-bottom: 10px; text-align: left; }
    .total-container { text-align: center; margin-top: 20px; padding: 20px; border-top: 1px solid #eee; }
    .total-container .total-label { font-size: 1.5em; color: #333; font-weight: normal; }
    .total-container .total-amount { font-size: 2em; color: #000; font-weight: normal; display: block; margin-top: 5px; }
    .table > thead > tr > th { font-weight: 600; color: #555; }

        .crumb-link,
.crumb-link:focus,
.crumb-link:active,
.crumb-link:focus-visible {
  outline: none !important;
  box-shadow: none !important;
  border: 0 !important;
}
.crumb-link::-moz-focus-inner { border: 0; }
.crumb-link { -webkit-tap-highlight-color: transparent; }

    /* Apila las dos cajas principales en pantallas medianas */
    @media (max-width: 991px) {
        .equal-panels > [class*="col-"] {
            margin-bottom: 20px;
        }
    }
    
    /* Ajustes para móviles (tablets pequeñas y teléfonos) */
    @media (max-width: 767px) {
        /* Ajuste para la lista de 'Datos de la persona' */
        .resumen.dl-horizontal dt { float: none; width: auto; font-weight: bold; }
        .resumen.dl-horizontal dd { margin-left: 0; margin-bottom: 15px; }
        
        /* === ESTILOS MEJORADOS PARA LA TABLA RESPONSIVA === */
        .tabla-tramites thead { display: none; }
        .tabla-tramites tr {
            display: block; border: 1px solid #ddd; border-radius: 4px;
            margin-bottom: 20px; padding: 15px;
        }
        .tabla-tramites td {
            display: block; text-align: right; position: relative;
            padding-left: 50%; border-bottom: 1px solid #eee;
            padding-top: 10px; padding-bottom: 10px; word-wrap: break-word;
        }
        .tabla-tramites td:last-child { border-bottom: none; }
        .tabla-tramites td:first-child {
            padding-left: 0; text-align: left; font-weight: bold; font-size: 1.1em;
            border-bottom: 1px solid #ccc; margin-bottom: 10px;
        }
        .tabla-tramites td:not(:first-child)::before {
            content: attr(data-label); position: absolute; left: 0;
            font-weight: 600; color: #555; text-align: left;
        }
        .tabla-tramites td:first-child::before { display: none; }
    }

    /* Ajustes para los pasos y botones en móviles */
    @media (max-width:575px){
      #pasos .nav-pills{ flex-direction:column; align-items:center; flex-wrap:nowrap; }
      #pasos .nav-pills>li>a{ width:100%; max-width:280px; min-width:240px; }
      .nav-actions .btn{ width:100%; display:inline-block; }
      .nav-actions .col-xs-6{ width:100%; float:none; }
      .nav-actions .col-xs-6 + .col-xs-6{ margin-top:10px; text-align:center; }
    }
  </style>
  
  {{-- Breadcrumb, Título y Pasos --}}
  <ol class="breadcrumb" style="margin-top:10px">
    <li><a href="{{ url('/') }}" class="crumb-link">Inicio</a></li>
    <li>Instituto Mexicano del Transporte</li>
  </ol>
  <center><h1 style="margin:10px 0 6px;">Instituto Mexicano del Transporte</h1></center>
  <br>
  <div id="pasos" class="text-center" style="margin-bottom:20px">
    <ul class="nav nav-pills">
        <li><a href="#"><strong>Paso 1</strong><small>Selección del trámite</small></a></li>
        <li><a href="#"><strong>Paso 2</strong><small>Información de la persona</small></a></li>
        <li class="active"><a href="#" aria-current="step"><strong>Paso 3</strong><small>Formato de pago</small></a></li>
    </ul>
  </div>

  <h3 style="margin-top:0">Resumen del trámite y datos de la persona:</h3>
  <div style="height:4px; width:48px; background:#a57f2c; margin:6px 0 18px;"></div>

  <div class="row equal-panels">
    {{-- Resumen de la persona --}}
    <div class="col-md-6">
      <div class="caja">
        <center><h4>Datos de la persona</h4></center>
        <hr>
        <dl class="dl-horizontal resumen">
          <dt>Tipo</dt><dd>{{ $personaData['tipo_persona'] === 'fisica' ? 'Persona Física' : 'Persona Moral' }}</dd>
          @if ($personaData['tipo_persona'] === 'fisica')
            <dt>CURP</dt><dd>{{ $personaData['curp'] }}</dd>
            <dt>RFC</dt><dd>{{ $personaData['rfc'] }}</dd>
            <dt>Nombre completo</dt><dd>{{ $personaData['nombres'] }} {{ $personaData['apellido_paterno'] }} {{ $personaData['apellido_materno'] }}</dd>
          @else
            <dt>RFC</dt><dd>{{ $personaData['rfc'] }}</dd>
            <dt>Razón social</dt><dd>{{ $personaData['razon_social'] }}</dd>
          @endif
        </dl>
      </div>
    </div>

    {{-- Resumen de trámites seleccionados --}}
    <div class="col-md-6">
        <div class="caja">
            <center><h4>Trámites seleccionados</h4></center>
            <hr>
            <table class="table tabla-tramites">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-right">Cuota</th>
                        <th class="text-right">IVA</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalGeneralSinRedondear = 0; @endphp
                    @foreach($tramites as $tramite)
                        @php
                            $montoIva = $tramite->iva ? round($tramite->cuota * 0.16, 2) : 0;
                            $totalTramite = $tramite->cuota + $montoIva;
                            $totalGeneralSinRedondear += $totalTramite;
                        @endphp
                        <tr>
                            <td data-label="Descripción">{{ $tramite->descripcion }}</td>
                            <td data-label="Cuota" class="text-right">${{ number_format($tramite->cuota, 2) }}</td>
                            <td data-label="IVA" class="text-right">${{ number_format($montoIva, 2) }}</td>
                            <td data-label="Total" class="text-right">${{ number_format($totalTramite, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
  </div>

  {{-- Contenedor para el total general --}}
  <div class="total-container">
    <span class="total-label">Importe total a pagar:</span>
    {{-- ========================================================== --}}
    {{-- INICIO DE LA CORRECCIÓN                                    --}}
    {{-- Se redondea el total general antes de mostrarlo.           --}}
    {{-- ========================================================== --}}
    @php $totalRedondeado = round($totalGeneralSinRedondear); @endphp
    <span class="total-amount">${{ number_format($totalRedondeado, 2) }} MXN</span>
  </div>

  {{-- Acciones --}}
  <div class="row nav-actions" style="margin-top:16px">
    <div class="col-xs-6">
      <a href="{{ route('regresar') }}?paso_actual=pago" class="btn btn-gob-outline" aria-label="Regresar al paso anterior">Regresar</a>
    </div>
    <div class="col-xs-6 text-right">
      <form action="{{ route('linea.generar') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-gob-outline" aria-label="Generar línea de captura">Generar línea de captura</button>
      </form>
    </div>
  </div>
  <br>
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
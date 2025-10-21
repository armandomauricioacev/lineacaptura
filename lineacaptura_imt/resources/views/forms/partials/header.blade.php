@php
  // PRODUCCIÓN v3 (oficial)
  $GOB_CSS = 'https://framework-gb.cdn.gob.mx/gm/v3/assets/styles/main.css';
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>@yield('title', 'Línea de Captura')</title>
  <link rel="icon" href="/favicon.ico">

  {{-- CSS oficial GOB.MX (PROD v3) --}}
  <link rel="stylesheet" href="{{ $GOB_CSS }}">

  {{-- Fuente Montserrat (Carga correcta y moderna) --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* Variables de color para fácil acceso */
    :root {
      --color-dorado-hover: #EABE3F; /* Tono dorado para hover/active */
    }

    /* Solo tipografía, sin alterar colores del framework */
    body, h1, h2, h3, h4, h5, h6 {
      font-family: "Montserrat", "Open Sans", Arial, sans-serif;
    }

    /* === Eliminar tooltips (title) en header y footer === */
    header a[title],
    header button[title],
    header .navbar a[title],
    header .navbar button[title],
    footer a[title],
    footer button[title],
    footer .accordion a[title],
    footer .nav-list a[title] { position: relative; }

    header a[title]:hover::after,
    header button[title]:hover::after,
    header .navbar a[title]:hover::after,
    header .navbar button[title]:hover::after,
    footer a[title]:hover::after,
    footer button[title]:hover::after,
    footer .accordion a[title]:hover::after,
    footer .nav-list a[title]:hover::after {
      content: none !important; display: none !important;
    }

    /* Ocultar tooltips nativos del navegador */
    header [title]:hover::before,
    header [title]:hover::after,
    footer [title]:hover::before,
    footer [title]:hover::after { display: none !important; }

    /* Eliminar efectos de fondo/borde en header (no tocamos colores) */
    header.main-header a:hover,
    header.main-header a:focus,
    header.main-header button:hover,
    header.main-header button:focus,
    .navbar a:hover,
    .navbar a:focus,
    .navbar button:hover,
    .navbar button:focus,
    .navbar-collapse a:hover,
    .navbar-collapse a:focus,
    .nav-item a:hover,
    .nav-item a:focus,
    .nav-link:hover,
    .nav-link:focus {
      background-color: transparent !important;
      background: transparent !important;
      box-shadow: none !important;
      border: none !important;
      outline: none !important;
      text-decoration: none !important;
    }

    /* Eliminar subrayado en TODOS los estados del header */
    header.main-header a,
    .navbar a,
    .navbar-nav .nav-link,
    .navbar-nav .btn,
    header a,
    header button { text-decoration: none !important; }

    header.main-header a:hover,
    header.main-header a:active,
    header.main-header a:focus,
    header.main-header a:visited,
    .navbar a:hover,
    .navbar a:active,
    .navbar a:focus,
    .navbar a:visited,
    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link:active,
    .navbar-nav .nav-link:focus,
    .navbar-nav .nav-link:visited { text-decoration: none !important; }

    /* Botones del framework sin fondos en hover */
    .btn-link:hover,
    .btn-link:focus,
    .btn-default:hover,
    .btn-default:focus {
      background-color: transparent !important;
      background: transparent !important;
      box-shadow: none !important;
      border: none !important;
    }

    /* Forzar transparencia en elementos del navbar (fondo) */
    nav[role="navigation"] *:hover,
    nav[role="navigation"] *:focus {
      background-color: transparent !important;
      box-shadow: none !important;
    }

    /* === Estilos para vista móvil (header) === */
    @media (max-width: 991px) {
      .navbar-toggler { align-self: flex-start !important; margin-top: -4px !important; }

      /* Icono hamburguesa */
      .navbar-toggler .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='white' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        transition: transform 0.3s ease-in-out, background-image 0.3s ease-in-out !important;
      }

      /* Icono X al abrir */
      .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon {
        transform: rotate(180deg) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='white' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M6 6L24 24M6 24L24 6'/%3e%3c/svg%3e") !important;
      }

      /* Color base móvil */
      .navbar-nav .nav-link,
      .navbar-nav .btn { color: #ffffff !important; }

      /* Dorado en tap móvil */
      .navbar-nav .nav-link:active,
      .navbar-nav .btn:active { color: var(--color-dorado-hover) !important; }
    }

    /* === Estilos para footer (se mantienen tus colores) === */

    /* Quitar outlines/box-shadows molestos */
    footer a,
    footer a:focus,
    footer a:active,
    footer a:visited,
    footer .accordion a,
    footer .accordion a:focus,
    footer .accordion a:active,
    footer .nav-list a,
    footer .nav-list a:focus,
    footer .nav-list a:active {
      outline: none !important;
      border: none !important;
      box-shadow: none !important;
    }

    /* Acordeón: labels sin recuadro (desktop) */
    footer .accordion label,
    footer .accordion label:focus,
    footer .accordion label:active,
    footer label[for^="toggle"],
    footer label[for^="toggle"]:focus,
    footer label[for^="toggle"]:active {
      outline: none !important;
      border: none !important;
      box-shadow: none !important;
    }

    /* Inputs del acordeón sin recuadro */
    footer .accordion-toggle,
    footer .accordion-toggle:focus,
    footer .accordion-toggle:active,
    footer input[type="checkbox"],
    footer input[type="checkbox"]:focus {
      outline: none !important;
      border: none !important;
      box-shadow: none !important;
    }

    /* Desktop: labels del acordeón no clicables */
    @media (min-width: 992px) {
      footer .accordion label,
      footer .accordion label h5,
      footer .accordion label h3,
      footer .accordion-toggle + label,
      footer label[for^="toggle"],
      footer .sitemap-list .sitemap-item-title,
      footer .sitemap-list h3,
      footer h3.sitemap-item-title,
      footer .sitemap-item-title,
      footer .sitemap h3,
      footer h3 { cursor: default !important; }

      footer .accordion label { pointer-events: none !important; }
    }

    /* Móvil: acordeón normal */
    @media (max-width: 991px) {
      footer .accordion label {
        cursor: pointer !important;
        pointer-events: auto !important;
      }
    }

    /* Enlaces siempre clicables */
    footer .accordion a,
    footer a { pointer-events: auto !important; cursor: pointer !important; }

    /* Dorados del footer (se dejan igual) */
    @media (min-width: 992px) {
      footer a:hover,
      footer .sitemap-list a:hover,
      footer .sitemap-links a:hover {
        color: var(--color-dorado-hover) !important;
        transition: color 0.2s ease-in-out;
      }
    }

    @media (max-width: 991px) {
      footer a:active,
      footer .sitemap-list a:active,
      footer .sitemap-links a:active {
        color: var(--color-dorado-hover) !important;
      }
    }

    /* Alineaciones menores del footer */
    footer .accordion section p { margin-top: 0 !important; padding-top: 0 !important; }
    footer .accordion section div { padding-top: 0 !important; }
    footer .accordion section { padding-top: 0 !important; }

    /* === HEADER: aplicar EXACTO el dorado del footer (bloque al final para ganar especificidad) === */
    @media (min-width: 992px) {
      /* Casos comunes en gob.mx / Bootstrap */
      header .navbar-default .navbar-nav > li > a:hover,
      header .navbar-default .navbar-nav > li > a:focus,
      header .navbar .navbar-nav > li > a:hover,
      header .navbar .navbar-nav > li > a:focus,
      header .navbar-nav > li > a:hover,
      header .navbar-nav > li > a:focus,
      header .nav > li > a:hover,
      header .nav > li > a:focus,
      header .nav-link:hover,
      header .nav-link:focus,
      header .navbar .btn-link:hover,
      header .navbar .btn-link:focus {
        color: var(--color-dorado-hover) !important;
        text-decoration: none !important;
        transition: color 0.2s ease-in-out;
      }
      /* Si el :hover está en el <li>, pinta el <a> interno */
      header .navbar-nav > li:hover > a,
      header .nav > li:hover > a {
        color: var(--color-dorado-hover) !important;
      }
    }

    @media (max-width: 991px) {
      header .navbar-default .navbar-nav > li > a:active,
      header .navbar .navbar-nav > li > a:active,
      header .navbar-nav > li > a:active,
      header .nav > li > a:active,
      header .nav-link:active,
      header .navbar .btn-link:active {
        color: var(--color-dorado-hover) !important;
      }
    }
  </style>

  <script>
    // Función para eliminar todos los atributos title
    function removeAllTitles() {
      const allElements = document.querySelectorAll('[title]');
      allElements.forEach(function(element) { element.removeAttribute('title'); });
    }

    document.addEventListener('DOMContentLoaded', function() {
      removeAllTitles();
      setTimeout(removeAllTitles, 100);
      setTimeout(removeAllTitles, 300);
      setTimeout(removeAllTitles, 500);
      setTimeout(removeAllTitles, 1000);
      setTimeout(removeAllTitles, 2000);
    });

    window.addEventListener('load', function() {
      removeAllTitles();
      setTimeout(removeAllTitles, 500);
    });

    // Observar cambios en el DOM y eliminar titles dinámicos
    if (window.MutationObserver) {
      const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'title') {
            mutation.target.removeAttribute('title');
          }
          if (mutation.addedNodes.length > 0) {
            removeAllTitles();
          }
        });
      });

      document.addEventListener('DOMContentLoaded', function() {
        observer.observe(document.body, {
          attributes: true,
          childList: true,
          subtree: true,
          attributeFilter: ['title']
        });
      });
    }
  </script>
</head>
<body>
  <main class="page">
    <div class="container">
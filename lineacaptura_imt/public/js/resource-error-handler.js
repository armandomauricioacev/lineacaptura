// Resource Error Handler - Maneja errores 404 y redirige a archivos fallback locales
(function() {
    'use strict';
    
    // Mapeo de recursos externos a archivos locales fallback
    const fallbackMap = {
        'ajax.googleapis.com/ajax/libs/webfont/1/webfont.js': '/js/webfont.js',
        'eu2dpadevsta020.blob.core.windows.net/boton/bootstrap.css': '/css/bootstrap.css',
        'eu2dpadevsta020.blob.core.windows.net/boton/Site.css': '/css/Site.css',
        'eu2dpadevsta020.blob.core.windows.net/boton/boton.png': '/img/boton.png'
    };
    
    // Función para convertir HTTP a HTTPS en URLs de Google Fonts
    function upgradeGoogleFontsToHttps(url) {
        if (url.includes('http://fonts.googleapis.com')) {
            return url.replace('http://fonts.googleapis.com', 'https://fonts.googleapis.com');
        }
        if (url.includes('http://fonts.gstatic.com')) {
            return url.replace('http://fonts.gstatic.com', 'https://fonts.gstatic.com');
        }
        return url;
    }
    
    // Interceptar llamadas de WebFont.load para corregir URLs HTTP
    function interceptWebFont() {
        // Guardar WebFont original si existe
        const originalWebFont = window.WebFont;
        
        // Sobrescribir WebFont.load
        window.WebFont = window.WebFont || {};
        const originalLoad = window.WebFont.load;
        
        window.WebFont.load = function(config) {
            if (config && config.google && config.google.families) {
                console.log('Interceptando WebFont.load con config:', config);
                
                // Si hay una URL personalizada, actualizarla a HTTPS
                if (config.google.api) {
                    config.google.api = upgradeGoogleFontsToHttps(config.google.api);
                }
                
                // Crear un elemento link con HTTPS para cargar las fuentes
                const fontUrl = 'https://fonts.googleapis.com/css?family=' + 
                    config.google.families.map(family => family.replace(/\s+/g, '+')).join('|');
                
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = fontUrl;
                document.head.appendChild(link);
                
                // Llamar callback active si se proporciona
                if (config.active) {
                    setTimeout(config.active, 100);
                }
                
                return;
            }
            
            // Llamar load original si existe
            if (originalLoad) {
                return originalLoad.call(this, config);
            }
        };
        
        // Copiar otras propiedades del WebFont original
        if (originalWebFont) {
            Object.keys(originalWebFont).forEach(key => {
                if (key !== 'load') {
                    window.WebFont[key] = originalWebFont[key];
                }
            });
        }
    }
    
    // Función para obtener el fallback local
    function getFallbackUrl(originalUrl) {
        for (const [pattern, fallback] of Object.entries(fallbackMap)) {
            if (originalUrl.includes(pattern)) {
                return fallback;
            }
        }
        return null;
    }
    
    // Interceptar y corregir URLs de Google Fonts antes de que se carguen
    function interceptGoogleFonts() {
        // Interceptar elementos <link> existentes
        const links = document.querySelectorAll('link[href*="fonts.googleapis.com"]');
        links.forEach(link => {
            const originalHref = link.href;
            const httpsHref = upgradeGoogleFontsToHttps(originalHref);
            if (originalHref !== httpsHref) {
                console.log('Actualizando Google Fonts de HTTP a HTTPS:', httpsHref);
                link.href = httpsHref;
            }
        });
        
        // Interceptar @import en estilos inline
        const styles = document.querySelectorAll('style');
        styles.forEach(style => {
            if (style.textContent.includes('http://fonts.googleapis.com')) {
                style.textContent = style.textContent.replace(
                    /http:\/\/fonts\.googleapis\.com/g, 
                    'https://fonts.googleapis.com'
                );
                console.log('Actualizando @import de Google Fonts a HTTPS');
            }
        });
    }
    
    // Inicializar interceptación de WebFont inmediatamente
    interceptWebFont();
    
    // Ejecutar al cargar la página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', interceptGoogleFonts);
    } else {
        interceptGoogleFonts();
    }
    
    // Observar cambios en el DOM para interceptar contenido dinámico
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Verificar si es un link de Google Fonts
                    if (node.tagName === 'LINK' && node.href && node.href.includes('fonts.googleapis.com')) {
                        const httpsHref = upgradeGoogleFontsToHttps(node.href);
                        if (node.href !== httpsHref) {
                            console.log('Interceptando nuevo link de Google Fonts:', httpsHref);
                            node.href = httpsHref;
                        }
                    }
                    
                    // Verificar links dentro del nodo añadido
                    const innerLinks = node.querySelectorAll && node.querySelectorAll('link[href*="fonts.googleapis.com"]');
                    if (innerLinks) {
                        innerLinks.forEach(link => {
                            const httpsHref = upgradeGoogleFontsToHttps(link.href);
                            if (link.href !== httpsHref) {
                                console.log('Interceptando link interno de Google Fonts:', httpsHref);
                                link.href = httpsHref;
                            }
                        });
                    }
                }
            });
        });
    });
    
    observer.observe(document, { childList: true, subtree: true });
    
    // Manejar errores de imágenes
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            const img = e.target;
            const src = img.src;
            const fallbackUrl = getFallbackUrl(src);
            
            if (fallbackUrl) {
                console.log('Cargando imagen fallback:', fallbackUrl);
                img.src = fallbackUrl;
            }
        }
    }, true);
    
    // Manejar errores de CSS
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'LINK' && e.target.rel === 'stylesheet') {
            const link = e.target;
            const href = link.href;
            const fallbackUrl = getFallbackUrl(href);
            
            if (fallbackUrl) {
                console.log('Cargando CSS fallback:', fallbackUrl);
                link.href = fallbackUrl;
            }
        }
    }, true);
    
    // Manejar errores de scripts
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'SCRIPT') {
            const script = e.target;
            const src = script.src;
            const fallbackUrl = getFallbackUrl(src);
            
            if (fallbackUrl) {
                console.log('Cargando script fallback:', fallbackUrl);
                const newScript = document.createElement('script');
                newScript.src = fallbackUrl;
                newScript.async = script.async;
                newScript.defer = script.defer;
                script.parentNode.insertBefore(newScript, script.nextSibling);
            }
        }
    }, true);
    
})();
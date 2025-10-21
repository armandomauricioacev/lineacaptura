/* WebFont Loader Fallback */
(function() {
    'use strict';
    
    // Simple WebFont loader fallback
    window.WebFont = window.WebFont || {
        load: function(config) {
            // Fallback implementation - just log that fonts are being loaded
            if (config && config.google && config.google.families) {
                console.log('WebFont fallback: Loading Google fonts', config.google.families);
            }
            
            // Trigger active callback if provided
            if (config && config.active) {
                setTimeout(config.active, 100);
            }
        }
    };
    
    // Mark as loaded
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = window.WebFont;
    }
})();
(function( $ ) { 'use strict';

    $( document ).ready( function() {
        var WMCA_Admin = {
            init: function() {
                this.settingTabs();
                this.generalScript();
            },

            /**
             * Admin Script
             */
            settingTabs: function() {
                $( '#setting_tabs' ).tabs().parents( '.wmca-settings-wrapper' ).show();
                $( '.wmca-notice' ).removeClass( 'hidden' );
            },

            generalScript: function() {

            }
        };

        WMCA_Admin.init();
    });
})( jQuery );
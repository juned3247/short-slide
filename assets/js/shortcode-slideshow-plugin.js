(function () {
    tinymce.create("tinymce.plugins.shortcode_slideshow", {
        init: function (editor, url) {
            editor.addButton('shortcode_slideshow', {
                title: "Insert slideshow",
                cmd: 'shortcode_slideshow_command',
                image : "/wp-content/plugins/short-slide/assets/images/slideshow_icon.png"
            });

            editor.addCommand('shortcode_slideshow_command', function () {
                tinymce.activeEditor.execCommand('mceInsertContent', false, "[myslideshow]");
            });
        },

        createControl : function(n, cm) {
            return null;
        },

        getInfo : function() {
            return {
                longname : "Shortcode for slideshow",
                author : "Juned Khatri",
                version : "1"
            };
        }
    });
    tinymce.PluginManager.add("shortcode_slideshow", tinymce.plugins.shortcode_slideshow);
})();
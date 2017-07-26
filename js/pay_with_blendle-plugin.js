(function() {
    tinymce.create('tinymce.plugins.BlendleButton', {
        init: function(editor, url) {
            editor.addCommand('wrapContentWithButtonShortcode', function() {
                var postContent = tinymce.activeEditor.getContent(),
                    selection = tinyMCE.activeEditor.selection.getContent(),
                    shortcodeInPostContent = postContent.indexOf('<p>[blendlebutton]</p>') > -1,
                    updatedPostContent;

                if (shortcodeInPostContent) {
                    updatedPostContent = postContent
                        .replace('<p>[blendlebutton]</p>', '')
                        .replace('<p>[/blendlebutton]</p>', '');

                    tinyMCE.activeEditor.setContent(updatedPostContent);
                } else {
                    updatedPostContent = '<p>[blendlebutton]</p>' + selection + '<p>[/blendlebutton]</p>';
                    tinyMCE.activeEditor.selection.setContent(updatedPostContent);
                }
            });

            editor.addButton('BlendleButton', {
                title: 'Insert Blendle Button shortcode',
                cmd: 'wrapContentWithButtonShortcode',
                image: url.replace('js', 'assets/blendle-logo.png')
            });
        }
    });

    // Register plugin
    tinymce.PluginManager.add('BlendleButton', tinymce.plugins.BlendleButton);
})();

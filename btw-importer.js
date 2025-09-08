// btw-importer.js
jQuery(document).ready(function($) {
    let posts = [];
    let currentIndex = 0;

    function logProgress(message) {
        $('#progress').append($('<div>').text(message));
    }

    function importNextPost() {
        if (currentIndex >= posts.length) {
            logProgress('✅ Import complete.');
            return;
        }

        $.post(btw_importer_data.ajaxUrl, {
            action: 'btw_importer_import_single_post',
            nonce: btw_importer_data.nonce,
            post: posts[currentIndex]
        }, function(response) {
            if (response.success) {
                response.data.forEach(logProgress);
            } else {
                logProgress('❌ Error: ' + response.data);
            }
            currentIndex++;
            importNextPost();
        });
    }

    $('#startImport').on('click', function() {
        const fileInput = $('#atomFile')[0];
        if (!fileInput.files.length) {
            alert('Please select an Atom (.xml) file first.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const atomContent = e.target.result;

            // Show parsing message
            logProgress('📦 Parsing Atom file...');

            $.post(btw_importer_data.ajaxUrl, {
                action: 'btw_importer_prepare_import',
                nonce: btw_importer_data.nonce,
                atom_content: atomContent
            }, function(response) {
                if (response.success) {
                    posts = response.data.posts;
                    logProgress('✅ Found ' + posts.length + ' posts. Starting import...');
                    importNextPost();
                } else {
                    logProgress('❌ Failed to parse Atom file: ' + response.data);
                }
            });
        };
        reader.readAsText(fileInput.files[0]);
    });
});

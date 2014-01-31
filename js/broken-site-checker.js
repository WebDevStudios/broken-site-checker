jQuery(document).ready(function($) {

        // Retrieve credly category results via AJAX
        $('#broken_site_checker_submit').click( function( event ) {

            // Stop the default submission from happening
            event.preventDefault();

            // Get sites
            $.ajax({
                type : "post",
                dataType : "json",
                url : ajaxurl,
                data : { "action": "maintainn_get_blog_ids" },
                success : function(response) {
                    check_sites( response );
                }
            });

        });

        function check_sites( site_ids ){

            // Show status div
            $('.sites-checked-header').show('slow');

            // Loop through and check each site
            check_site( site_ids, 0 );

        }

        function check_site( site_ids, index ){

            $.ajax({
                type : "post",
                dataType : "json",
                url : ajaxurl,
                data : { "action": "maintainn_check_broken_site", "site_id": site_ids[index] },
                success : function(response) {

                    // Add our markup
                    $('#sites-checked').append(response);

                    // Increase the index
                    ++index;

                    // If we haven't iterated through all, keep going
                    if (index < site_ids.length)
                        check_site( site_ids, index );

                }

            });

        }

});
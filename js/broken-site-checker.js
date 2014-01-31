jQuery(document).ready(function($) {

        // Retrieve site ids via AJAX
        $('#broken_site_checker_submit').click( function( event ) {

            // Stop the default submission from happening
            event.preventDefault();

            // Show status div
            $('.sites-checked-header').show('slow');

            // Loop through and check each site
            check_site( site_ids, 0 );

        });

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

                    if (index < site_ids.length)
                    {
                        // If we haven't iterated through all, keep going
                        check_site( site_ids, index );
                    }
                    else
                    {
                        // Otherwise show finished message
                        $('#sites-checked-finished').show();
                    }

                }

            });

        }

});
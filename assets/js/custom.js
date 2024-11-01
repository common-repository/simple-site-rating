jQuery( document ).ready(function($) {

    $(document).on('click', '.wsmssr-page-feed-container .icon', function(event) {
        
        event.preventDefault();
        var feed = $(this).attr('data');
        
        if( feed == 'very_good' || feed == 'good' || feed == 'bad' || feed == 'very_bad' ){
            $.ajax( {
                url: ajax_var.url,
                type: 'post',
                data: {
                    action: 'wsmssr_save_feed',
                    nonce: ajax_var.nonce,   // pass the nonce here
                    id : ajax_var.post_id,
                    feed: feed
                },
                success( data ) {
                    if ( data ) {
                        $('.wsmssr_title_question').css('display','none');
                        $('.wsmssr_thankyou_heading').css('display','block');
                        $('.wsmssr_thankyou_text').css('display','block');
                        $('.wsmssr-page-feed-answer .icon').css('display','none');
                    }
                },
            } );
        }

    });

});
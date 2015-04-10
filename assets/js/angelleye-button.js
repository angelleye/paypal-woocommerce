//@angel you need to add param day on admin panel
function setCookie(key, value) {
            var expires = new Date();
            var day=365;
            expires.setTime(expires.getTime() + (day * 24 * 60 * 60 * 1000));
            document.cookie = key + '=' + value +';path=/'+ ';expires=' + expires.toUTCString();
}
function getCookie(key) {
            var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
            return keyValue ? keyValue[2] : null;
}


//@angel you need to add param file cookie on admin panel, here cherryclass-cgv-v1
jQuery(document).ready(function ($){
     /* show popup if not accepted*/
    $("#paypal_ec_button_product input").on('click', function(event){
       if(getCookie('cherryclass-cgv-v1')!=1){
                $(".cgv-popup").addClass("is-visible");
               
            }else{
                var angelleye_action = $(this).data('action');
                $('form.cart').attr( 'action', angelleye_action );
                $(this).attr('disabled', 'disabled');
                $('form.cart').submit();
                $(".angelleyeOverlay").show();
        }
        return false;

    });
    /* show popup if not accepted*/
    $(".paypal_checkout_button").on('click', function(event){
        if(getCookie('cherryclass-cgv-v1')!=1){
                $(".cgv-popup").addClass("is-visible");
                return false;
        }else{
                $(".angelleyeOverlay").show();
                return true;
        }
    });
    /* show popup on first add for the user. Can t set up if accept redirect to checkout*/
    /*$('.add_to_cart_button').on('click', function(event){
            if(getCookie('cherryclass-cgv-v1')==null){
                $(".cgv-popup").addClass("is-visible");
            }
    });*/
    /* terms accepted cookie for 1 year, start paypal checkout*/
    $('.cgv-accept').on('click', function(event){
        $(".cgv-popup").removeClass("is-visible");
        setCookie('cherryclass-cgv-v1','1');
        var a_href = $('.paypal_checkout_button').attr('href');    
         window.open(a_href,'_self');
                $(".angelleyeOverlay").show();
                return false;               
        
    }); 
    /* terms accepted refused, reload for a bug to animation button on widget*/
    $('.cgv-refused').on('click', function(event){
        $(".cgv-popup").removeClass("is-visible");
        setCookie('cherryclass-cgv-v1','0');  
        location.reload();              
        
    });
});

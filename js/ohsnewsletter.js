jQuery( document ).ready(function( $ ) {
    $(".ohsi-sendgrid-widget .ohssubmit").click(function() {
        var that = $(this);
        that.attr('disabled', true);
        that.val("Subscribing...");
        var parent = $(this).parent();
        var fn = $('.ohsnl-fn', parent).val();
        var ln = $('.ohsnl-ln', parent).val();
        var em = $('.ohsnl-em', parent).val();
        console.log(em);
        $.ajax({
            url: ohsNL.API_sub,
            type: 'POST',
            dataType: 'json',
            data: {
                first_name: fn,
                last_name: ln,
                email: em
            },
            success: function(data) {
                //console.log(data);
                parent.slideUp();
                $(".ohsi-msg", parent.parent()).html(data);
            },
            error: function(e) {
                //console.log(e.responseJSON);
                $(".ohsi-msg", parent.parent()).html("");
                for (var i = 0; i < e.responseJSON.length; i++) {
                    $(".ohsi-msg", parent.parent()).append("<span class='text-danger'>" + e.responseJSON[i] + "</span><br>");
                }
                that.attr('disabled', false);
                that.val("Subscribe");
            }
        });
        console.log(ohsNL.API_sub);
    });
});
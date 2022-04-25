$(document).ready(function () {
    var $widgetHdButton = $('.open-packeta-hd-widget');
    if ($widgetHdButton.length === 1) {
        $.getScript('https://hd.widget.packeta.com/www/js/library-hd.js').fail(function () {
            console.error('Unable to load Packeta home delivery widget.');
        });

        if ($('.picked-delivery-place').data('validated') === '') {
            $('.validatedAddress').hide();
        }

        var widgetHdOptionsData = $widgetHdButton.data('widget-options');
        var widgetHdOptions = {
            layout: 'hd',
            carrierId: widgetHdOptionsData['carrierId'],
            country: widgetHdOptionsData['country'],
            language: widgetHdOptionsData['language'],
            street: widgetHdOptionsData['street'],
            houseNumber: widgetHdOptionsData['houseNumber'],
            city: widgetHdOptionsData['city'],
            postcode: widgetHdOptionsData['zip']
        };
        $widgetHdButton.on('click', function (event) {
            event.preventDefault();
            PacketaHD.Widget.pick(widgetHdOptionsData['apiKey'], function (result) {
                if (result !== null && result.address !== null) {
                    var address = result.address;
                    $('.packetery form input[name="address"]').val(JSON.stringify(address));
                    $('.packetery-street').text(address.street + ' ' + address.houseNumber);
                    $('.packetery-city').text(address.city);
                    $('.packetery-zip').text(address.postcode);
                    $('.packetery-county').text(address.county);
                    $('.packetery-gps').text(address.latitude + ', ' + address.longitude);
                    $('.validatedAddress').show();
                }
            }, widgetHdOptions);
        });
    }

    var $widgetButton = $('.open-packeta-widget');
    if ($widgetButton.length === 1) {
        $.getScript("https://widget.packeta.com/v6/www/js/library.js")
            .fail(function () {
                console.error('Unable to load Packeta Widget.');
            });

        var widgetOptionsData = $widgetButton.data('widget-options');
        var widgetOptions = {
            appIdentity: widgetOptionsData['app_identity'],
            country: widgetOptionsData['country'],
            language: widgetOptionsData['lang']
        };
        if (widgetOptionsData['carriers']) {
            widgetOptions.carriers = widgetOptionsData['carriers'];
        }

        $widgetButton.on('click', function (event) {
            event.preventDefault();
            Packeta.Widget.pick(widgetOptionsData['api_key'], function (pickupPoint) {
                if (pickupPoint !== null) {
                    $('.packetery form input[name="pickup_point"]').val(JSON.stringify(pickupPoint));
                    $('.picked-delivery-place').text(pickupPoint.name);
                }
            }, widgetOptions);
        });
    }
});

$(document).ready(function () {
    $('#process_post_parcel').on('click',function(event){
        if(!confirm(process_post_parcel_confirmation)){
            event.preventDefault();
        }
    });
});

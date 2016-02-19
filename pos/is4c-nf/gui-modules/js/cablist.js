var cablist = (function($) {
    var mod = {};

    mod.submitWrapper = function(urlStem) {
        var ref = $('#selectlist').val();
        if (ref != ""){
            $.ajax({
                url: urlStem + 'ajax-callbacks/AjaxCabReceipt.php',
                type: 'get',
                cache: false,
                data: 'input='+ref
            }).done(function(data){
                window.location = urlStem + 'gui-modules/pos2.php';
            });
        } else {
            window.location = urlStem+'gui-modules/pos2.php';
        }

        return false;
    };

    return mod;
}(jQuery));
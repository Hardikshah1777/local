define(['jquery'], function($) {
    return {
        initialize: function() {
            var oldselected1 = $('#id_profile_field_dropdown1').html();
            var oldselected2 = $('#id_profile_field_dropdown2').html();
            $('#id_profile_field_nicensenumber_nothing').on('change', function() {
                if ($(this).prop('checked')) {
                    $('#id_profile_field_dropdown1').html('<option value="None">None</option>');
                    $('#id_profile_field_dropdown2').html('<option value="None">None</option>');
                }
                else {
                    $('#id_profile_field_dropdown1').html(oldselected1);
                    $('#id_profile_field_dropdown2').html(oldselected2);
                }
            });
        }
    };
});

(function($) {
    $(document).ready(function() {
        /**
         * A generic method for adding a new field to a multi-field control.
         */
        $(document).on("click", ".jspace-add-field", function(e) {
            e.preventDefault();

            var root = $(this).parent();
            var prefix = root.attr('data-jspace-name');
            var fields = root.children('.jspace-control');
            var length = fields.length;

            var maximum = root.data('jspace-maximum');

            if (length >= maximum)
                return;
            
            var template = fields.last().clone();
            
            $.each(template.children(":input"), function(i, input) {
                if ($(input).attr('name')) {
                    var search = template.data('jspace-name');
                    var replace = prefix+'['+length+']';

                    $(input).attr('name', $(input).attr('name').replace(search, replace));
                    $(input).val("");
                }
            });
            
            template.data('jspace-name', prefix+'['+(length)+']');
            template.attr('data-jspace-name', template.data('jspace-name'));
            fields.last().after(template);

            $(this).data('jspace-length', length+1);
        });

        /**
         * A generic method for removing an existing field from a multi-field control.
         */        
        $(document).on("click", ".jspace-remove-field", function(e) {
            e.preventDefault();

            var root = $(this).parent().parent();
            var prefix = root.attr('data-jspace-name');
            var fields = root.children('.jspace-control');
            var length = fields.length;

            if (length == 1)
                return;
            
            $(this).parent().remove();
                    
            $.each(fields, function(i, field) {
                $.each($(field).children(":input"), function(j, input) {                    
                    if ($(input).attr('name')) {
                        var search = $(field).data('jspace-name');
                        var replace = prefix+'['+i+']';

                        $(input).attr('name', $(input).attr('name').replace(search, replace));
                    }
                });

                $(field).attr('data-jspace-name', prefix+'['+i+']');
            });
            
            $(".jspace-add-field").data("jspace-length", length-1);
        });
    })
})(jQuery);
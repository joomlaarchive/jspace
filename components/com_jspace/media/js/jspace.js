(function($) {
    $(document).ready(function() {
        /**
         * A generic method for adding a new field to a multi-field control.
         */
        $(document).on("click", ".jspace-add-field", function(e) {
            e.preventDefault();

            var root = $(this).parent();
            var length = root.children('.jspace-control').length;
            var maximum = root.data('jspace-maximum');

            if (length >= maximum)
                return;
            
            var template = root.children('.jspace-control').last().clone();
            
            template.children(":input").val("");

            root.children('.jspace-control').last().after(template);
            
            $.each(root.children('.jspace-control'), function(i, field) {
                // only jspace controls with multi-inputs should be reindexed.
                if ($(field).children(":input").length > 1) {
                    
                    $.each($(field).children(":input"), function(j, input) {
                        if ($(input).attr('name')) {
                            var name = $(input).attr('name');
                            name = name.replace(/\[[0-9]+\]/g, '['+i+']');

                            $(input).attr('name', name);
                        }
                    });
                }
            });
        });

        /**
         * A generic method for removing an existing field from a multi-field control.
         */        
        $(document).on("click", ".jspace-remove-field", function(e) {
            e.preventDefault();

            var root = $(this).parent().parent();
            var length = root.children('.jspace-control').length;

            if (length == 1)
                return;
            
            $(this).parent().remove();
                 
            $.each(root.children('.jspace-control'), function(i, field) {
                // only jspace controls with multi-inputs should be reindexed.
                if ($(field).children(":input").length > 1) {
                
                    $.each($(field).children(":input"), function(j, input) {                    
                        if ($(input).attr('name')) {
                            var name = $(input).attr('name');
                            name = name.replace(/\[[0-9]+\]/g, '['+i+']');

                            $(input).attr('name', name);
                        }
                    });
                }
            });
        });
    })
})(jQuery);
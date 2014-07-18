(function($) {
    $(document).ready(function() {
        /**
         * A generic method for adding a new field to a multi-field control.
         */
        $(document).on("click", ".jspace-add-field", function(e) {
            e.preventDefault();
            
            var template = $(this.parentElement).clone();
            
            var name = template.data('name');
            var id = template.data('id');
            var position = template.data('position');
            var maximum = template.data('maximum');
            
            if (position >= maximum)
                return;
            
            template.data('position', parseInt(position)+1);
            template.attr('data-position', template.data('position'));
            
            template.children().each(function(index) {
                if ($(this).attr('id')) {
                    var search = id+'_'+position;
                    var replace = id+'_'+template.data('position');
                    
                    $(this).attr('id', $(this).attr('id').replace(search, replace));
                }
                
                if ($(this).attr('name')) {
                    var search = name+'['+position+']';
                    replace = name+'['+template.data('position')+']';
                    
                    $(this).attr('name', $(this).attr('name').replace(search, replace));
                }
            });
            
            remove = $('.jspace-remove-field:first').clone();
            
            $(this.parentElement).append(remove);
            $(this.parentElement).after(template);
            
            this.remove();
        });

        /**
         * A generic method for removing an existing field from a multi-field control.
         */        
        $(document).on("click", ".jspace-remove-field", function(e) {
            e.preventDefault();
            
            var name = $(this.parentElement).data('name');
            var id = $(this.parentElement).data('id');
            
            $(this.parentElement).remove();
            
            $('*[data-id="'+id+'"]').each(function(i, parent) {
                $(this).children().each(function(j, child) {
                    if ($(this).attr('id')) {
                        var search = id+'_'+$(parent).data('position');
                        var replace = id+'_'+i;
                        console.log(search);
                        console.log(replace);
                        $(this).attr('id', $(this).attr('id').replace(search, replace));
                    }
                    
                    if ($(this).attr('name')) {
                        var search = name+'['+$(parent).data('position')+']';
                        var replace = name+'['+i+']';
                        
                        $(this).attr('name', $(this).attr('name').replace(search, replace));
                    }
                });
                
                $(parent).data("position", i);
                $(parent).attr("data-position", $(parent).data("position"));
            });
        });
    })
})(jQuery);
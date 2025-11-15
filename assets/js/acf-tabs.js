jQuery(document).ready(function($) {
    // Initialize all ACF tabs containers
    $('.acf-tabs-container').each(function() {
        var $container = $(this);
        var $spinner = $container.find('.spinner');
        var $status = $container.find('.acf-tabs-status');
        
        // Set first tab as active by default
        $container.find(".acf-tab-button").first().addClass("active");
        $container.find(".acf-tab-content").first().addClass("active");
        
        // Convert static content to editable textareas
        $container.find('.acf-tab-content').each(function() {
            var $content = $(this);
            var content = $content.html().trim();
            var fieldName = $content.attr('id').replace('acf-tab-', '');
            
            // Replace content with textarea
            $content.html('<textarea class="acf-tab-textarea" data-field="' + fieldName + '" style="width: 100%; min-height: 200px;">' + 
                         content.replace(/<\/textarea>/gi, '&lt;/textarea>') + '</textarea>');
            
            // Store initial value
            var $textarea = $content.find('textarea');
            $textarea.data('initial', $textarea.val());
            
            // Auto-resize textarea
            function adjustHeight() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight + 2) + 'px';
            }
            
            // Initial adjustment
            adjustHeight.call($textarea[0]);
            
            // Bind input event for auto-resize and change detection
            $textarea.on('input', function() {
                adjustHeight.call(this);
                checkForChanges();
            });
        });
        
        // Check for changes in any field
        function checkForChanges() {
            var hasChanges = false;
            $container.find(".acf-tab-textarea").each(function() {
                if ($(this).val() !== $(this).data('initial')) {
                    hasChanges = true;
                    return false; // break the loop
                }
            });
            setDirty(hasChanges);
            return hasChanges;
        }
        
        // Update UI based on dirty state
        function setDirty(isDirty) {
            $container.find(".acf-tabs-save, .acf-tabs-discard").prop("disabled", !isDirty);
            if (isDirty) {
                $status.html('<span style="color:#dba617;">You have unsaved changes</span>').show();
            } else {
                $status.html('<span style="color:#00a32a;">All changes saved</span>');
                setTimeout(function() {
                    $status.fadeOut(500, function() {
                        $status.html('').show();
                    });
                }, 3000);
            }
        }
        
        // Handle tab switching
        $container.on("click", ".acf-tab-button", function(e) {
            e.preventDefault();
            
            // Check for unsaved changes
            if (checkForChanges()) {
                if (!confirm('You have unsaved changes. Are you sure you want to switch tabs?')) {
                    return false;
                }
            }
            
            var $button = $(this);
            var tabId = $button.data("tab");
            
            // Update active state
            $button.addClass("active").siblings().removeClass("active");
            $container.find("#" + tabId)
                    .addClass("active")
                    .siblings(".acf-tab-content")
                    .removeClass("active");
                    
            // Re-check for changes after tab switch
            checkForChanges();
        });

        // Discard changes
        $container.on("click", ".acf-tabs-discard", function(){
            if (confirm('Are you sure you want to discard all changes?')) {
                $container.find(".acf-tab-textarea").each(function(){
                    var $field = $(this);
                    $field.val($field.data("initial"));
                    // Trigger height adjustment
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight + 2) + 'px';
                });
                setDirty(false);
            }
        });

        // Save changes
        $container.on("click", ".acf-tabs-save", function(){
            var $button = $(this);
            var productName = $container.data('product-name');
            var updates = [];
            
            // Find all changed fields
            $container.find(".acf-tab-textarea").each(function() {
                var $field = $(this);
                var currentValue = $field.val();
                var initialValue = $field.data('initial');
                
                if (currentValue !== initialValue) {
                    updates.push({
                        field: $field.data('field'),
                        value: currentValue
                    });
                }
            });
            
            if (updates.length === 0) {
                setDirty(false);
                return;
            }
            
            // Disable buttons and show spinner
            $button.prop('disabled', true);
            $container.find('.acf-tabs-discard').prop('disabled', true);
            $spinner.addClass('is-active');
            $status.html('Saving changes...').show();
            
            // Prepare data for AJAX
            var data = {
                action: 'update_acf_fields',
                nonce: wbAcfTabs.nonce,
                product_name: productName,
                updates: updates
            };
            
            // Send AJAX request
            $.ajax({
                url: wbAcfTabs.ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update initial values
                        $container.find(".acf-tab-textarea").each(function(){
                            var $field = $(this);
                            $field.data('initial', $field.val());
                        });
                        setDirty(false);
                        $status.html('<span style="color:#00a32a;">' + (response.data || 'Changes saved successfully') + '</span>');
                    } else {
                        $status.html('<span style="color:#d63638;">Error: ' + (response.data || 'Failed to save changes') + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $status.html('<span style="color:#d63638;">Error: Could not save changes. Please try again.</span>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $container.find('.acf-tabs-discard').prop('disabled', false);
                    $spinner.removeClass('is-active');
                    
                    // Clear status after 5 seconds
                    setTimeout(function() {
                        if (!$container.find('.acf-tabs-save').is(':disabled')) {
                            $status.fadeOut(500, function() {
                                $status.html('').show();
                            });
                        }
                    }, 5000);
                }
            });
        });
        
        // Initial check for changes
        checkForChanges();
    });
});

/**
 * Traffic Portal Link Shortener - Frontend JavaScript
 * Handles form validation, AJAX submissions, and user interactions
 */
(function($) {
    'use strict';

    // Main application object
    const TrafficPortalApp = {
        
        // Configuration
        config: {
            validateDelay: 500,
            keyMinLength: 3,
            keyMaxLength: 20,
            keyPattern: /^[a-zA-Z0-9_-]+$/
        },

        // State management
        state: {
            isValidating: false,
            isCreating: false,
            lastValidatedKey: null,
            keyAvailable: false
        },

        // DOM elements cache
        elements: {},

        /**
         * Initialize the application
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.initializeForm();
        },

        /**
         * Cache frequently used DOM elements
         */
        cacheElements: function() {
            this.elements = {
                $form: $('#traffic-portal-form'),
                $keyInput: $('#tpkey'),
                $destinationInput: $('#destination'),
                $submitBtn: $('#save-link'),
                $generateBtn: $('#generate-key'),
                $result: $('#traffic-portal-result'),
                $resultContent: $('.result-content'),
                $shortLinkDisplay: $('.short-link-display'),
                $shortLinkUrl: $('.short-link-url'),
                $copyBtn: $('.copy-link'),
                $validationFeedback: $('.validation-feedback')
            };
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Form submission
            this.elements.$form.on('submit', function(e) {
                e.preventDefault();
                self.handleFormSubmit();
            });

            // Key input validation with debounce
            let validateTimeout;
            this.elements.$keyInput.on('input', function() {
                clearTimeout(validateTimeout);
                const key = $(this).val().trim();
                
                if (key.length >= self.config.keyMinLength) {
                    validateTimeout = setTimeout(function() {
                        self.validateKey(key);
                    }, self.config.validateDelay);
                } else {
                    self.clearValidation();
                }
            });

            // Generate random key
            this.elements.$generateBtn.on('click', function() {
                self.generateRandomKey();
            });

            // Copy link functionality
            this.elements.$copyBtn.on('click', function(e) {
                e.preventDefault();
                self.copyToClipboard();
            });

            // Real-time URL validation
            this.elements.$destinationInput.on('input', function() {
                const url = $(this).val();
                self.validateUrl(url);
            });
        },

        /**
         * Initialize form state
         */
        initializeForm: function() {
            // Generate initial random key if field is empty
            if (!this.elements.$keyInput.val()) {
                this.generateRandomKey();
            }

            // Focus on destination field if key already has value
            if (this.elements.$keyInput.val()) {
                this.elements.$destinationInput.focus();
            }
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function() {
            if (this.state.isCreating) {
                return;
            }

            const formData = this.getFormData();
            
            if (!this.validateFormData(formData)) {
                return;
            }

            this.createShortLink(formData);
        },

        /**
         * Get form data
         */
        getFormData: function() {
            return {
                tpkey: this.elements.$keyInput.val().trim(),
                destination: this.elements.$destinationInput.val().trim(),
                domain: $('input[name="domain"]').val(),
                nonce: $('input[name="nonce"]').val()
            };
        },

        /**
         * Validate form data
         */
        validateFormData: function(data) {
            let isValid = true;

            // Validate key
            if (!data.tpkey || data.tpkey.length < this.config.keyMinLength) {
                this.showValidationError('tpkey', tpls_ajax.messages.error_generic);
                isValid = false;
            } else if (!this.config.keyPattern.test(data.tpkey)) {
                this.showValidationError('tpkey', 'Key can only contain letters, numbers, underscore and dash');
                isValid = false;
            }

            // Validate URL
            if (!data.destination || !this.isValidUrl(data.destination)) {
                this.showValidationError('destination', tpls_ajax.messages.error_invalid);
                isValid = false;
            }

            return isValid;
        },

        /**
         * Validate key availability
         */
        validateKey: function(key) {
            if (this.state.isValidating || this.state.lastValidatedKey === key) {
                return;
            }

            this.state.isValidating = true;
            this.state.lastValidatedKey = key;

            const self = this;
            const $feedback = this.elements.$keyInput.siblings('.validation-feedback');
            
            $feedback.removeClass('valid invalid')
                    .addClass('validating')
                    .text(tpls_ajax.messages.validating);

            $.ajax({
                url: tpls_ajax.rest_url + 'validate',
                method: 'GET',
                data: {
                    tpkey: key,
                    domain: $('input[name="domain"]').val()
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', tpls_ajax.nonce);
                },
                success: function(response) {
                    self.handleKeyValidationResponse(response, key);
                },
                error: function(xhr) {
                    self.handleKeyValidationError(xhr);
                },
                complete: function() {
                    self.state.isValidating = false;
                }
            });
        },

        /**
         * Handle key validation response
         */
        handleKeyValidationResponse: function(response, key) {
            const $feedback = this.elements.$keyInput.siblings('.validation-feedback');
            
            if (response && response.success && response.keystatus === 'available') {
                this.state.keyAvailable = true;
                $feedback.removeClass('validating invalid')
                        .addClass('valid')
                        .text('✓ Key is available');
                this.elements.$keyInput.removeClass('is-invalid').addClass('is-valid');
            } else {
                this.state.keyAvailable = false;
                const message = response && response.message ? response.message : tpls_ajax.messages.error_key_used;
                $feedback.removeClass('validating valid')
                        .addClass('invalid')
                        .text('✗ ' + message);
                this.elements.$keyInput.removeClass('is-valid').addClass('is-invalid');
            }
        },

        /**
         * Handle key validation error
         */
        handleKeyValidationError: function(xhr) {
            const $feedback = this.elements.$keyInput.siblings('.validation-feedback');
            $feedback.removeClass('validating valid')
                    .addClass('invalid')
                    .text('✗ Unable to validate key');
            this.elements.$keyInput.removeClass('is-valid').addClass('is-invalid');
            this.state.keyAvailable = false;
        },

        /**
         * Clear validation state
         */
        clearValidation: function() {
            const $feedback = this.elements.$keyInput.siblings('.validation-feedback');
            $feedback.removeClass('validating valid invalid').text('');
            this.elements.$keyInput.removeClass('is-valid is-invalid');
            this.state.keyAvailable = false;
            this.state.lastValidatedKey = null;
        },

        /**
         * Validate URL format
         */
        validateUrl: function(url) {
            const $feedback = this.elements.$destinationInput.siblings('.validation-feedback');
            
            if (!url) {
                $feedback.removeClass('valid invalid').text('');
                this.elements.$destinationInput.removeClass('is-valid is-invalid');
                return;
            }

            if (this.isValidUrl(url)) {
                $feedback.removeClass('invalid').addClass('valid').text('✓ Valid URL');
                this.elements.$destinationInput.removeClass('is-invalid').addClass('is-valid');
            } else {
                $feedback.removeClass('valid').addClass('invalid').text('✗ Please enter a valid URL');
                this.elements.$destinationInput.removeClass('is-valid').addClass('is-invalid');
            }
        },

        /**
         * Check if URL is valid
         */
        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },

        /**
         * Generate random key
         */
        generateRandomKey: function() {
            const key = this.generateKey(6);
            this.elements.$keyInput.val(key).trigger('input');
        },

        /**
         * Generate random string
         */
        generateKey: function(length) {
            const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            return result;
        },

        /**
         * Create short link via AJAX
         */
        createShortLink: function(formData) {
            if (!this.state.keyAvailable) {
                this.showResult('error', 'Please choose a different key - this one is not available.');
                return;
            }

            this.state.isCreating = true;
            this.setLoadingState(true);

            const self = this;

            $.ajax({
                url: tpls_ajax.rest_url + 'create',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', tpls_ajax.nonce);
                },
                success: function(response) {
                    self.handleCreateResponse(response, formData);
                },
                error: function(xhr) {
                    self.handleCreateError(xhr);
                },
                complete: function() {
                    self.state.isCreating = false;
                    self.setLoadingState(false);
                }
            });
        },

        /**
         * Handle create link response
         */
        handleCreateResponse: function(response, formData) {
            if (response && response.success) {
                const shortUrl = `https://${formData.domain}/${formData.tpkey}`;
                this.showSuccessResult(shortUrl);
                this.resetForm();
            } else {
                const message = response && response.message ? response.message : tpls_ajax.messages.error_generic;
                this.showResult('error', message);
            }
        },

        /**
         * Handle create link error
         */
        handleCreateError: function(xhr) {
            let message = tpls_ajax.messages.error_generic;
            
            if (xhr.status === 401) {
                message = tpls_ajax.messages.login_required;
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            
            this.showResult('error', message);
        },

        /**
         * Show success result with short link
         */
        showSuccessResult: function(shortUrl) {
            this.elements.$shortLinkUrl.attr('href', shortUrl).text(shortUrl);
            this.elements.$shortLinkDisplay.show();
            this.showResult('success', tpls_ajax.messages.success);
        },

        /**
         * Show result message
         */
        showResult: function(type, message) {
            this.elements.$result.removeClass('success error info')
                                 .addClass(type)
                                 .show();
            this.elements.$resultContent.html(message);

            // Auto-hide error messages after 5 seconds
            if (type === 'error') {
                setTimeout(() => {
                    this.elements.$result.fadeOut();
                }, 5000);
            }

            // Scroll to result
            this.elements.$result[0].scrollIntoView({ behavior: 'smooth' });
        },

        /**
         * Show validation error
         */
        showValidationError: function(field, message) {
            const $input = field === 'tpkey' ? this.elements.$keyInput : this.elements.$destinationInput;
            const $feedback = $input.siblings('.validation-feedback');
            
            $feedback.removeClass('valid validating')
                    .addClass('invalid')
                    .text('✗ ' + message);
            $input.removeClass('is-valid').addClass('is-invalid');
        },

        /**
         * Set loading state
         */
        setLoadingState: function(loading) {
            if (loading) {
                this.elements.$submitBtn.addClass('loading').prop('disabled', true);
                this.elements.$submitBtn.find('.btn-text').text(tpls_ajax.messages.creating);
                this.elements.$form.addClass('loading');
            } else {
                this.elements.$submitBtn.removeClass('loading').prop('disabled', false);
                this.elements.$submitBtn.find('.btn-text').text('Save');
                this.elements.$form.removeClass('loading');
            }
        },

        /**
         * Reset form after successful submission
         */
        resetForm: function() {
            // Generate new random key
            this.generateRandomKey();
            
            // Clear destination
            this.elements.$destinationInput.val('');
            
            // Clear validation states
            this.clearValidation();
            this.elements.$destinationInput.removeClass('is-valid is-invalid');
            this.elements.$destinationInput.siblings('.validation-feedback').removeClass('valid invalid').text('');
            
            // Focus on destination input
            setTimeout(() => {
                this.elements.$destinationInput.focus();
            }, 100);
        },

        /**
         * Copy link to clipboard
         */
        copyToClipboard: function() {
            const url = this.elements.$shortLinkUrl.text();
            
            if (navigator.clipboard && window.isSecureContext) {
                // Use modern clipboard API
                navigator.clipboard.writeText(url).then(() => {
                    this.showCopySuccess();
                }).catch(() => {
                    this.fallbackCopy(url);
                });
            } else {
                // Fallback for older browsers
                this.fallbackCopy(url);
            }
        },

        /**
         * Fallback copy method
         */
        fallbackCopy: function(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showCopySuccess();
            } catch (err) {
                this.showResult('error', 'Unable to copy to clipboard. Please copy manually.');
            }
            
            document.body.removeChild(textArea);
        },

        /**
         * Show copy success feedback
         */
        showCopySuccess: function() {
            const $btn = this.elements.$copyBtn;
            const originalText = $btn.text();
            
            $btn.text('Copied!').addClass('btn-success');
            
            setTimeout(() => {
                $btn.text(originalText).removeClass('btn-success');
            }, 2000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#traffic-portal-form').length) {
            TrafficPortalApp.init();
        }
    });

})(jQuery);
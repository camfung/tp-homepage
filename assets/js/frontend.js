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
            keyAvailable: false,
            retryCount: 0,
            maxRetries: 2
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
                $validationFeedback: $('.validation-feedback'),
                $qrDisplay: $('#qr-code-display')
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

            // Key input basic validation
            this.elements.$keyInput.on('input', function() {
                const key = $(this).val().trim();
                self.validateKeyFormat(key);
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

            this.createShortLinkLocal(formData);
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
                this.showValidationError('tpkey', 'Please enter at least ' + this.config.keyMinLength + ' characters');
                isValid = false;
            } else if (!this.config.keyPattern.test(data.tpkey)) {
                this.showValidationError('tpkey', 'Key can only contain letters, numbers, underscore and dash');
                isValid = false;
            }

            // Validate URL
            if (!data.destination || !this.isValidUrl(data.destination)) {
                this.showValidationError('destination', 'Please enter a valid URL');
                isValid = false;
            }

            return isValid;
        },

        /**
         * Validate key format
         */
        validateKeyFormat: function(key) {
            const $feedback = this.elements.$keyInput.siblings('.validation-feedback');

            if (!key) {
                $feedback.removeClass('valid invalid').text('');
                this.elements.$keyInput.removeClass('is-valid is-invalid');
                return;
            }

            if (key.length >= this.config.keyMinLength && this.config.keyPattern.test(key)) {
                $feedback.removeClass('invalid').addClass('valid').text('✓ Key format is valid');
                this.elements.$keyInput.removeClass('is-invalid').addClass('is-valid');
            } else {
                $feedback.removeClass('valid').addClass('invalid').text('✗ 3-20 characters: letters, numbers, underscore, dash');
                this.elements.$keyInput.removeClass('is-valid').addClass('is-invalid');
            }
        },

        /**
         * Create short link locally (no API call)
         */
        createShortLinkLocal: function(formData) {
            this.state.isCreating = true;
            this.setLoadingState(true);

            // Create the short URL directly
            const shortUrl = `https://trfc.link/${formData.tpkey}`;

            // Simulate a brief delay for better UX
            setTimeout(() => {
                this.state.isCreating = false;
                this.setLoadingState(false);
                this.showSuccessResultWithQR(shortUrl);
                this.resetForm();
            }, 500);
        },

        /**
         * Clear validation state
         */
        clearValidation: function() {
            const $feedback = this.elements.$keyInput.siblings('.validation-feedback');
            $feedback.removeClass('validating valid invalid').text('');
            this.elements.$keyInput.removeClass('is-valid is-invalid');
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
         * Create QR Code using provided function
         */
        createQRCode: function(url, options = {}) {
            // Create a container div for the QR code
            const container = document.createElement('div');
            container.className = 'qr-container';

            // Generate unique ID for this instance
            const qrId = 'qr-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            container.id = qrId;

            // Default options
            const defaultOptions = {
                width: 156,
                height: 156,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            };

            // Merge options
            const qrOptions = { ...defaultOptions, ...options };

            // Create QR code after element is in DOM
            setTimeout(() => {
                new QRCode(container, {
                    text: url,
                    width: qrOptions.width,
                    height: qrOptions.height,
                    colorDark: qrOptions.colorDark,
                    colorLight: qrOptions.colorLight,
                    correctLevel: qrOptions.correctLevel
                });

                // Make the QR code responsive to parent size
                const style = document.createElement('style');
                style.textContent = `
                    #${qrId} {
                        width: 100%;
                        height: 100%;
                    }
                    #${qrId} canvas,
                    #${qrId} img {
                        width: 100% !important;
                        height: 100% !important;
                        object-fit: contain;
                    }
                `;
                document.head.appendChild(style);
            }, 0);

            return container;
        },

        /**
         * Show success result with short link and QR code
         */
        showSuccessResultWithQR: function(shortUrl) {
            this.elements.$shortLinkUrl.attr('href', shortUrl).text(shortUrl);

            // Clear previous QR code
            this.elements.$qrDisplay.empty();

            // Generate new QR code
            const qrElement = this.createQRCode(shortUrl);
            this.elements.$qrDisplay.append(qrElement);

            this.elements.$shortLinkDisplay.show();
            this.showResult('success', 'Short link created successfully!');
        },

        /**
         * Show success result with short link (legacy)
         */
        showSuccessResult: function(shortUrl) {
            this.showSuccessResultWithQR(shortUrl);
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
                this.elements.$submitBtn.find('.btn-text').text('Creating...');
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
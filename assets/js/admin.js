/**
 * Traffic Portal Link Shortener - Admin JavaScript
 * Handles admin dashboard functionality
 */
(function($) {
    'use strict';

    // Admin application object
    const TrafficPortalAdmin = {

        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.initializeDataTable();
            this.initializeTooltips();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Copy link functionality
            $('.copy-link').on('click', function(e) {
                e.preventDefault();
                const link = $(this).data('link');
                self.copyToClipboard(link, $(this));
            });

            // Delete link with confirmation
            $('.delete-link').on('click', function(e) {
                if (!confirm(tpls_admin.messages.confirm_delete)) {
                    e.preventDefault();
                }
            });

            // Toggle link status via AJAX
            $('.toggle-status').on('click', function(e) {
                e.preventDefault();
                const linkId = self.getLinkIdFromUrl($(this).attr('href'));
                if (linkId) {
                    self.toggleLinkStatus(linkId, $(this));
                }
            });

            // Quick actions
            this.bindQuickActions();

            // Search and filter functionality
            this.bindSearchFilter();
        },

        /**
         * Initialize data table enhancements
         */
        initializeDataTable: function() {
            // Add zebra striping
            $('.wp-list-table tbody tr:even').addClass('alternate');

            // Add hover effects
            $('.wp-list-table tbody tr').hover(
                function() { $(this).addClass('hover'); },
                function() { $(this).removeClass('hover'); }
            );

            // Responsive table wrapper
            $('.wp-list-table').wrap('<div class="table-responsive"></div>');
        },

        /**
         * Initialize tooltips
         */
        initializeTooltips: function() {
            // Add tooltips to action links
            $('[data-tooltip]').each(function() {
                $(this).attr('title', $(this).data('tooltip'));
            });
        },

        /**
         * Bind quick action events
         */
        bindQuickActions: function() {
            const self = this;

            // Bulk actions (if implemented)
            $('#doaction, #doaction2').on('click', function(e) {
                const action = $(this).prev('select').val();
                const checkedItems = $('.wp-list-table input[type="checkbox"]:checked').not('#cb-select-all');
                
                if (action === 'delete' && checkedItems.length > 0) {
                    if (!confirm(`${tpls_admin.messages.confirm_delete} (${checkedItems.length} items)`)) {
                        e.preventDefault();
                    }
                }
            });

            // Select all functionality
            $('#cb-select-all').on('change', function() {
                $('.wp-list-table input[type="checkbox"]').not(this).prop('checked', this.checked);
            });
        },

        /**
         * Bind search and filter functionality
         */
        bindSearchFilter: function() {
            // Live search (if search box exists)
            const $searchBox = $('#link-search-input');
            if ($searchBox.length) {
                let searchTimeout;
                $searchBox.on('input', function() {
                    clearTimeout(searchTimeout);
                    const query = $(this).val().toLowerCase();
                    
                    searchTimeout = setTimeout(function() {
                        $('.wp-list-table tbody tr').each(function() {
                            const $row = $(this);
                            const text = $row.text().toLowerCase();
                            $row.toggle(text.indexOf(query) > -1);
                        });
                    }, 300);
                });
            }

            // Status filter
            const $statusFilter = $('#status-filter');
            if ($statusFilter.length) {
                $statusFilter.on('change', function() {
                    const status = $(this).val();
                    if (status === '') {
                        $('.wp-list-table tbody tr').show();
                    } else {
                        $('.wp-list-table tbody tr').each(function() {
                            const $row = $(this);
                            const rowStatus = $row.find('.status-badge').text().toLowerCase();
                            $row.toggle(rowStatus === status);
                        });
                    }
                });
            }
        },

        /**
         * Toggle link status via AJAX
         */
        toggleLinkStatus: function(linkId, $element) {
            const self = this;
            const $row = $element.closest('tr');
            const $statusBadge = $row.find('.status-badge');
            const originalStatus = $statusBadge.text();

            // Show loading state
            $element.text(tpls_admin.messages.updating);
            $row.addClass('updating');

            $.ajax({
                url: tpls_admin.ajax_url,
                method: 'POST',
                data: {
                    action: 'tpls_admin_toggle_status',
                    link_id: linkId,
                    nonce: tpls_admin.nonce
                },
                success: function(response) {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.success) {
                        // Update status badge
                        $statusBadge.removeClass('status-active status-inactive')
                                   .addClass('status-' + data.new_status)
                                   .text(data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1));
                        
                        // Update toggle link text
                        const newText = data.new_status === 'active' ? 'Deactivate' : 'Activate';
                        $element.text(newText);
                        
                        self.showNotice('success', tpls_admin.messages.success);
                    } else {
                        $element.text(originalStatus === 'Active' ? 'Deactivate' : 'Activate');
                        self.showNotice('error', data.message || tpls_admin.messages.error);
                    }
                },
                error: function() {
                    $element.text(originalStatus === 'Active' ? 'Deactivate' : 'Activate');
                    self.showNotice('error', tpls_admin.messages.error);
                },
                complete: function() {
                    $row.removeClass('updating');
                }
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text, $button) {
            const self = this;
            const originalText = $button.text();

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    self.showCopySuccess($button, originalText);
                }).catch(function() {
                    self.fallbackCopy(text, $button, originalText);
                });
            } else {
                self.fallbackCopy(text, $button, originalText);
            }
        },

        /**
         * Fallback copy method for older browsers
         */
        fallbackCopy: function(text, $button, originalText) {
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
                this.showCopySuccess($button, originalText);
            } catch (err) {
                this.showNotice('error', 'Unable to copy to clipboard');
                $button.text(originalText);
            }

            document.body.removeChild(textArea);
        },

        /**
         * Show copy success feedback
         */
        showCopySuccess: function($button, originalText) {
            $button.text('Copied!').addClass('copied');
            
            setTimeout(function() {
                $button.text(originalText).removeClass('copied');
            }, 2000);
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            // Remove existing notices
            $('.traffic-portal-notice').remove();
            
            // Create new notice
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible traffic-portal-notice" style="margin: 5px 0;">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            // Add to page
            $('.wrap h1').after($notice);
            
            // Bind dismiss functionality
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut();
            });
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        },

        /**
         * Extract link ID from URL
         */
        getLinkIdFromUrl: function(url) {
            const matches = url.match(/link_id=(\d+)/);
            return matches ? parseInt(matches[1]) : null;
        },

        /**
         * Initialize stats dashboard
         */
        initializeStats: function() {
            // If we have stats data, initialize charts or counters
            const $statsContainer = $('.traffic-portal-stats');
            if ($statsContainer.length) {
                this.loadStats();
            }
        },

        /**
         * Load and display stats
         */
        loadStats: function() {
            // This could be expanded to show charts, graphs, etc.
            // For now, just enhance the existing display
            $('.stats-number').each(function() {
                const $this = $(this);
                const target = parseInt($this.text());
                $this.text('0');
                
                // Animate counter
                $({ count: 0 }).animate({ count: target }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.count));
                    },
                    complete: function() {
                        $this.text(target);
                    }
                });
            });
        },

        /**
         * Export functionality
         */
        initializeExport: function() {
            const self = this;
            
            $('.export-links').on('click', function(e) {
                e.preventDefault();
                const format = $(this).data('format') || 'csv';
                self.exportLinks(format);
            });
        },

        /**
         * Export links data
         */
        exportLinks: function(format) {
            const self = this;
            
            // Show loading
            const $exportBtn = $(`.export-links[data-format="${format}"]`);
            const originalText = $exportBtn.text();
            $exportBtn.text('Exporting...').prop('disabled', true);
            
            $.ajax({
                url: tpls_admin.ajax_url,
                method: 'POST',
                data: {
                    action: 'tpls_export_links',
                    format: format,
                    nonce: tpls_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        self.showNotice('success', 'Export completed successfully');
                    } else {
                        self.showNotice('error', 'Export failed');
                    }
                },
                error: function() {
                    self.showNotice('error', 'Export failed');
                },
                complete: function() {
                    $exportBtn.text(originalText).prop('disabled', false);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.wrap').find('h1').text().indexOf('Short Links') > -1 || 
            $('.wrap').find('h1').text().indexOf('Traffic Portal') > -1) {
            TrafficPortalAdmin.init();
        }
    });

    // Additional CSS for admin enhancements
    const adminStyles = `
        <style>
        .wp-list-table tr.updating {
            opacity: 0.7;
            background-color: #f0f0f0;
        }
        
        .wp-list-table .copy-link.copied {
            color: #46b450 !important;
            font-weight: bold;
        }
        
        .status-badge.status-active {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-badge.status-inactive {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        @media (max-width: 782px) {
            .wp-list-table .column-primary {
                padding-right: 0;
            }
            
            .wp-list-table .copy-link {
                display: block;
                margin-top: 5px;
            }
        }
        
        .stats-number {
            font-size: 2em;
            font-weight: bold;
            color: #0073aa;
        }
        
        .export-buttons {
            margin: 10px 0;
        }
        
        .export-buttons .button {
            margin-right: 5px;
        }
        
        .traffic-portal-notice {
            position: relative;
        }
        </style>
    `;
    
    $('head').append(adminStyles);

})(jQuery);
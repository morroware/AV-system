/**
 * Combined JavaScript for AV Controls and Remote Control System
 * Enhanced with loading states, better feedback, and accessibility
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize both systems
    initializeReceiverControls();
    loadTransmitters();
    loadFavoriteChannels();
    initializeAccessibility();
});

// Add loading state to an element
function setLoading(element, isLoading) {
    if (isLoading) {
        element.classList.add('loading-state');
        element.disabled = true;
        element.dataset.originalText = element.textContent;
        if (element.tagName === 'BUTTON') {
            element.innerHTML = '<span class="spinner"></span> ' + element.textContent;
        }
    } else {
        element.classList.remove('loading-state');
        element.disabled = false;
        if (element.dataset.originalText) {
            element.textContent = element.dataset.originalText;
        }
    }
}

// Initialize accessibility features
function initializeAccessibility() {
    // Add keyboard navigation for remote buttons
    document.querySelectorAll('.remote-container button').forEach(button => {
        button.setAttribute('tabindex', '0');
    });

    // Announce changes to screen readers
    const announcer = document.createElement('div');
    announcer.setAttribute('aria-live', 'polite');
    announcer.setAttribute('aria-atomic', 'true');
    announcer.className = 'sr-only';
    announcer.id = 'announcer';
    document.body.appendChild(announcer);
}

function announce(message) {
    const announcer = document.getElementById('announcer');
    if (announcer) {
        announcer.textContent = message;
        setTimeout(() => announcer.textContent = '', 1000);
    }
}

// Receiver Control Functions - Modified for auto-submit
function initializeReceiverControls() {
    // Auto-submit for channel changes
    $(document).on('change', '.channel-select', function() {
        const select = $(this);
        const receiverCard = select.closest('.receiver');
        const deviceIp = select.data('ip') || receiverCard.data('ip');
        const channelName = select.find('option:selected').text();

        // Visual feedback
        receiverCard.addClass('updating');
        select.prop('disabled', true);

        const data = new FormData();
        data.append('receiver_ip', deviceIp);
        data.append('channel', this.value);

        $.ajax({
            url: '',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                showResponseMessage(response.success ? `Switched to ${channelName}` : response.message, response.success);
                announce(response.success ? `Channel changed to ${channelName}` : 'Channel change failed');
            },
            error: function() {
                showResponseMessage('Failed to update channel. Check device connection.', false);
            },
            complete: function() {
                receiverCard.removeClass('updating');
                select.prop('disabled', false);
            }
        });
    });

    // Also support the old class for backwards compatibility
    $(document).on('change', 'select.auto-submit', function() {
        const form = $(this).closest('form');
        const select = $(this);
        const receiverCard = select.closest('.receiver');

        receiverCard.addClass('updating');
        select.prop('disabled', true);

        const data = new FormData();
        data.append('receiver_ip', form.find('input[name="receiver_ip"]').val());
        data.append('channel', this.value);

        $.ajax({
            url: '',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.message) {
                    showResponseMessage(response.message, response.success);
                }
            },
            error: function() {
                showResponseMessage('Failed to update channel', false);
            },
            complete: function() {
                receiverCard.removeClass('updating');
                select.prop('disabled', false);
            }
        });
    });

    // Debounced auto-submit for volume changes
    let volumeTimeout = null;
    $(document).on('input', '.volume-slider, input[type="range"].auto-submit', function() {
        const slider = this;
        const $slider = $(slider);
        const receiverCard = $slider.closest('.receiver');
        const deviceIp = $slider.data('ip') || receiverCard.data('ip');

        // Update volume label immediately
        updateVolumeLabel(slider);

        // Debounce volume changes to avoid too many requests
        if (volumeTimeout) {
            clearTimeout(volumeTimeout);
        }

        volumeTimeout = setTimeout(() => {
            receiverCard.addClass('updating');

            const data = new FormData();
            data.append('receiver_ip', deviceIp);
            data.append('volume', slider.value);

            $.ajax({
                url: '',
                type: 'POST',
                data: data,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    // Only show message on error - volume feedback is visual
                    if (!response.success) {
                        showResponseMessage(response.message || 'Volume update failed', false);
                    }
                    announce(`Volume set to ${slider.value}`);
                },
                error: function() {
                    showResponseMessage('Failed to update volume. Check device connection.', false);
                },
                complete: function() {
                    receiverCard.removeClass('updating');
                }
            });
        }, 300); // 300ms debounce for volume changes
    });

    // Power On button handler with delayed second command
    $('#power-all-on').on('click', function() {
        // No response message - send command silently
        sendPowerCommandToAll('cec_tv_on.sh', false)
            .then(function() {
                console.log("First power-on command sent, will repeat in 30 seconds");
                
                // Set a timer to send the command again after 30 seconds
                setTimeout(function() {
                    sendPowerCommandToAll('cec_tv_on.sh', false)
                        .then(function() {
                            console.log("Second power-on command sent");
                        });
                }, 30000); // 30 seconds delay
            });
    });

    // Power Off button handler
    $('#power-all-off').on('click', function() {
        // No response message - send command silently
        sendPowerCommandToAll('cec_tv_off.sh', false);
    });
}

// Volume and Power functions
function updateVolumeLabel(slider) {
    const label = slider.parentElement.querySelector('.volume-label');
    if (label) {
        label.textContent = slider.value;
    }
}

function sendPowerCommand(deviceIp, command, showNotification = true) {
    return $.ajax({
        url: '',
        type: 'POST',
        data: {
            receiver_ip: deviceIp,
            power_command: command
        },
        dataType: 'json'
    }).then(function(response) {
        if (showNotification && response.message) {
            showResponseMessage(response.message, response.success);
        }
        return response;
    });
}

function sendPowerCommandToAll(command, showNotification = true) {
    const receivers = $('.receiver');
    let promises = [];

    receivers.each(function() {
        const deviceIp = $(this).find('input[name="receiver_ip"]').val();
        if (deviceIp) {
            promises.push(sendPowerCommand(deviceIp, command, false)); // Don't show individual notifications
        }
    });

    return Promise.all(promises);
}

// Response message handler
function showResponseMessage(message, success) {
    const responseElement = $('#response-message');
    if (!responseElement.length) return; // Skip if element doesn't exist
    
    responseElement
        .removeClass('success error')
        .addClass(success ? 'success' : 'error')
        .html(message)
        .fadeIn();

    setTimeout(() => responseElement.fadeOut(), 3000);
}

// Remote Control Functions
function loadTransmitters() {
    fetch('transmitters.txt')
        .then(response => response.text())
        .then(data => {
            const transmitters = data.split('\n').filter(line => line.trim() !== '');
            
            const select = document.createElement('select');
            select.id = 'transmitter';
            
            transmitters.forEach(transmitter => {
                const [name, url] = transmitter.split(',').map(item => item.trim());
                const option = document.createElement('option');
                option.value = url;
                option.textContent = name;
                select.appendChild(option);
            });
            
            const container = document.getElementById('transmitter-select');
            if (container) {
                container.innerHTML = 'Select Transmitter: ';
                container.appendChild(select);
            }
        })
        .catch(error => {
            console.error('Error loading transmitters:', error);
            showError('Failed to load transmitters');
        });
}

function sendCommand(action) {
    const transmitter = document.getElementById('transmitter');
    if (!transmitter || !transmitter.value) {
        showError('Please select a transmitter');
        return;
    }

    $.ajax({
        url: '', // Use current page - handled by BaseController
        type: 'POST',
        data: {
            device_url: transmitter.value,
            action: action
        },
        dataType: 'json'
    }).then(function(response) {
        if (response.success) {
            showResponseMessage('Command sent: ' + action, true);
        } else if (response.message) {
            showError(response.message);
        }
    }).fail(function(error) {
        showError('Failed to send command');
        console.error('Failed to send command:', error);
    });
}

function showError(message) {
    const errorElement = document.getElementById('error-message');
    if (!errorElement) return; // Skip if element doesn't exist

    const errorTextElement = document.getElementById('error-text');
    if (errorTextElement) {
        errorTextElement.textContent = message;
    }
    errorElement.style.display = 'block';
    announce(message);

    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 5000);
}

// Load favorite channels if available
function loadFavoriteChannels() {
    fetch('favorites.ini')
        .then(response => {
            if (!response.ok) return null;
            return response.text();
        })
        .then(data => {
            if (!data) return;

            const container = document.getElementById('favorite-channels-select');
            if (!container) return;

            const favorites = [];
            const lines = data.split('\n').filter(line => line.trim() && !line.startsWith('[') && !line.startsWith(';'));

            lines.forEach(line => {
                const [key, value] = line.split('=').map(s => s.trim().replace(/"/g, ''));
                if (key && value) {
                    favorites.push({ key, value });
                }
            });

            if (favorites.length === 0) {
                container.style.display = 'none';
                return;
            }

            let html = '<label for="favorites">Quick Channels:</label> <select id="favorites" aria-label="Quick channel selection">';
            html += '<option value="">Select a favorite...</option>';
            favorites.forEach(fav => {
                html += `<option value="${fav.key}">${fav.value}</option>`;
            });
            html += '</select>';
            container.innerHTML = html;

            // Handle favorite selection
            document.getElementById('favorites').addEventListener('change', function() {
                if (this.value) {
                    sendCommand(this.value);
                    this.value = ''; // Reset selection
                }
            });
        })
        .catch(error => {
            console.log('No favorites file or error loading:', error);
            const container = document.getElementById('favorite-channels-select');
            if (container) container.style.display = 'none';
        });
}

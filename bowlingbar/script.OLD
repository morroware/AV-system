/**
 * Combined JavaScript for AV Controls and Remote Control System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize both systems
    initializeReceiverControls();
    loadTransmitters();
});

// Receiver Control Functions - Modified for auto-submit
function initializeReceiverControls() {
    // Auto-submit for channel changes
    $(document).on('change', 'select.auto-submit', function() {
        const form = $(this).closest('form');
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
            }
        });
    });

    // Debounced auto-submit for volume changes
    let volumeTimeout = null;
    $(document).on('input', 'input[type="range"].auto-submit', function() {
        const slider = this;
        const form = $(slider).closest('form');

        // Update volume label immediately
        updateVolumeLabel(slider);

        // Debounce volume changes to avoid too many requests
        if (volumeTimeout) {
            clearTimeout(volumeTimeout);
        }

        volumeTimeout = setTimeout(() => {
            const data = new FormData();
            data.append('receiver_ip', form.find('input[name="receiver_ip"]').val());
            data.append('volume', slider.value);
            
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
                    showResponseMessage('Failed to update volume', false);
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
        url: 'api.php',
        type: 'POST',
        data: {
            device_url: transmitter.value,
            action: action
        },
        dataType: 'json'
    }).then(function(response) {
        if (response.success) {
            console.log("Remote command sent:", action);
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
    
    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 3000);
}

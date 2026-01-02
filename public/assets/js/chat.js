// chat.js - Handles real-time chat functionality

document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    let lastMessageId = 0;
    let isSending = false;
    let currentUserRole = '';

    if (!chatBox || !messageInput || !sendButton) {
        // Not on the chat page
        return;
    }

    // Function to fetch new messages
    function fetchMessages() {
        fetch(`chat_api.php?last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.current_user_role) {
                        currentUserRole = data.current_user_role;
                    }
                    if (data.messages.length > 0) {
                        checkForSignalingMessages(data.messages);
                        data.messages.forEach(message => {
                            appendMessage(message);
                            lastMessageId = Math.max(lastMessageId, message.id);
                        });
                        // Scroll to bottom after new messages are added
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                }
            })
            .catch(error => console.error('Error fetching messages:', error));
    }

    // Function to append a message to the chat box
    function appendMessage(message) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message');
        messageElement.classList.add(message.is_mine ? 'mine' : 'other');
        if (message.is_admin) {
            messageElement.classList.add('admin-message');
        }

        const roleTag = message.is_admin ? '<span class="role-tag">Admin</span>' : '';

        let messageBody = message.message;
        if (message.message_type === 'audio' && message.media_path) {
            messageBody = `<audio controls><source src="${message.media_path}" type="audio/webm"></audio>`;
        } else if (message.message_type === 'call') {
            messageBody = `Call ${message.call_duration ? `(${message.call_duration}s)` : ''}`;
        }

        let deleteButton = '';
        if (message.is_admin || message.is_mine) {
            deleteButton = `<button class="delete-message-btn" data-message-id="${message.id}">Delete</button>`;
        }

        messageElement.innerHTML = `
            <div class="message-header">
                <span class="username">${message.username} ${roleTag}</span>
                <span class="timestamp">${new Date(message.sent_at).toLocaleTimeString()}</span>
                ${deleteButton}
            </div>
            <div class="message-body">${messageBody}</div>
        `;
        chatBox.appendChild(messageElement);
    }

    // Function to send a message
    function sendMessage() {
        if (isSending) return;
        const messageText = messageInput.value.trim();
        if (messageText === '') return;

        isSending = true;
        fetch('chat_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: messageText }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = ''; // Clear input
                // Fetch all messages to ensure the new one is displayed correctly
                fetchMessages();
            } else {
                alert('Failed to send message: ' + data.error);
            }
        })
        .catch(error => console.error('Error sending message:', error))
        .finally(() => {
            isSending = false;
        });
    }



    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    const voiceCallButton = document.getElementById('voice-call-button');
    const videoCallButton = document.getElementById('video-call-button');
    const voicemailButton = document.getElementById('voicemail-button');
    const voicemailPreview = document.getElementById('voicemail-preview');
    const voicemailAudio = document.getElementById('voicemail-audio');
    const sendVoicemailButton = document.getElementById('send-voicemail');
    const cancelVoicemailButton = document.getElementById('cancel-voicemail');
    let isRecording = false;
    let mediaRecorder;
    let stream;
    let recordedBlob;

    if (voiceCallButton) {
        voiceCallButton.addEventListener('click', () => {
            console.log('Voice call button clicked');
            startCall('voice');
        });
    } else {
        console.error('Voice call button not found');
    }

    if (videoCallButton) {
        videoCallButton.addEventListener('click', () => {
            console.log('Video call button clicked');
            startCall('video');
        });
    } else {
        console.error('Video call button not found');
    }

    if (voicemailButton) {
        voicemailButton.addEventListener('click', () => {
            console.log('Voicemail button clicked');
            if (isRecording) {
                stopRecording();
            } else {
                startRecording();
            }
        });
    } else {
        console.error('Voicemail button not found');
    }

    if (sendVoicemailButton) {
        sendVoicemailButton.addEventListener('click', sendVoicemail);
    }

    if (cancelVoicemailButton) {
        cancelVoicemailButton.addEventListener('click', cancelVoicemail);
    }

    // Add video elements for calls
    const videoDiv = document.createElement('div');
    videoDiv.id = 'video-container';
    videoDiv.style.display = 'none';
    videoDiv.innerHTML = `
        <video id="local-video" autoplay muted></video>
        <video id="remote-video" autoplay></video>
    `;
    chatBox.parentNode.insertBefore(videoDiv, chatBox);

    // Function to start recording voicemail
    function startRecording() {
        console.log('Starting voicemail recording');
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(audioStream => {
                console.log('Got audio stream');
                stream = audioStream;
                mediaRecorder = new MediaRecorder(stream);
                const chunks = [];

                mediaRecorder.ondataavailable = event => {
                    console.log('Data available');
                    chunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    console.log('Recording stopped');
                    console.log('Chunks length:', chunks.length);
                    if (chunks.length > 0) {
                        recordedBlob = new Blob(chunks, { type: 'audio/webm' });
                        console.log('Blob size:', recordedBlob.size);
                        // Set the audio src for preview
                        voicemailAudio.src = URL.createObjectURL(recordedBlob);
                        voicemailAudio.load();
                        // Show the preview
                        voicemailPreview.style.display = 'block';
                        // Scroll to the preview
                        voicemailPreview.scrollIntoView({ behavior: 'smooth' });
                    } else {
                        console.error('No audio data recorded');
                        alert('No audio data recorded. Please try again.');
                    }
                };

                mediaRecorder.start();
                isRecording = true;
                console.log('Recording started');
                // Change button text to indicate recording
                voicemailButton.textContent = 'Stop Voicemail';
            })
            .catch(error => console.error('Error starting recording:', error));
    }

    // Function to stop recording voicemail
    function stopRecording() {
        if (mediaRecorder && isRecording) {
            mediaRecorder.stop();
            stream.getTracks().forEach(track => track.stop());
            isRecording = false;
            console.log('Recording stopped manually');
            // Change button text back
            voicemailButton.textContent = 'Voicemail';
        }
    }

    // Function to send voicemail
    function sendVoicemail() {
        if (recordedBlob) {
            const formData = new FormData();
            formData.append('audio', recordedBlob, 'voicemail.webm');

            // Upload the audio file
            fetch('upload_voicemail.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Upload response:', data);
                if (data.success) {
                    // Send message with media_id
                    fetch('chat_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: 'Voicemail',
                            message_type: 'audio',
                            media_id: data.media_id
                        })
                    })
                    .then(() => {
                        fetchMessages(); // Refresh messages
                        hideVoicemailPreview();
                    });
                }
            })
            .catch(error => console.error('Upload error:', error));
        }
    }

    // Function to cancel voicemail
    function cancelVoicemail() {
        hideVoicemailPreview();
    }

    // Function to hide voicemail preview
    function hideVoicemailPreview() {
        voicemailPreview.style.display = 'none';
        voicemailAudio.src = '';
        recordedBlob = null;
    }

    // Event listener for delete message buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-message-btn')) {
            const messageId = e.target.getAttribute('data-message-id');
            if (confirm('Are you sure you want to delete this message?')) {
                fetch('delete_message.php?message_id=' + messageId, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the message from the DOM
                        e.target.closest('.chat-message').remove();
                    } else {
                        alert('Failed to delete message: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting message:', error);
                    alert('Error deleting message');
                });
            }
        }
    });

    // Initial load and polling for new messages
    fetchMessages();
    setInterval(fetchMessages, 3000); // Poll every 3 seconds
});

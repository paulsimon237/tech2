// webrtc.js - Handles WebRTC for voice and video calls

let localStream;
let remoteStream;
let peerConnection;
let isCaller = false;
let callType = 'voice'; // 'voice' or 'video'

const configuration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' }
    ]
};

// Function to start a call
function startCall(type) {
    callType = type;
    navigator.mediaDevices.getUserMedia({ audio: true, video: type === 'video' })
        .then(stream => {
            localStream = stream;
            // Display local stream
            const localVideo = document.getElementById('local-video');
            if (localVideo) {
                localVideo.srcObject = stream;
            }
            // Show video container
            const videoContainer = document.getElementById('video-container');
            if (videoContainer) {
                videoContainer.style.display = 'block';
            }
            // Send offer to other user via chat
            createOffer();
        })
        .catch(error => console.error('Error accessing media devices:', error));
}

// Function to create offer
function createOffer() {
    peerConnection = new RTCPeerConnection(configuration);
    peerConnection.addStream(localStream);

    peerConnection.onicecandidate = event => {
        if (event.candidate) {
            // Send candidate via chat
            sendSignalingMessage('candidate', event.candidate);
        }
    };

    peerConnection.onaddstream = event => {
        remoteStream = event.stream;
        const remoteVideo = document.getElementById('remote-video');
        if (remoteVideo) {
            remoteVideo.srcObject = event.stream;
        }
    };

    peerConnection.createOffer()
        .then(offer => peerConnection.setLocalDescription(offer))
        .then(() => {
            // Send offer via chat
            sendSignalingMessage('offer', peerConnection.localDescription);
        });
}

// Function to handle incoming offer
function handleOffer(offer) {
    peerConnection = new RTCPeerConnection(configuration);
    peerConnection.addStream(localStream);

    peerConnection.onicecandidate = event => {
        if (event.candidate) {
            sendSignalingMessage('candidate', event.candidate);
        }
    };

    peerConnection.onaddstream = event => {
        remoteStream = event.stream;
        const remoteVideo = document.getElementById('remote-video');
        if (remoteVideo) {
            remoteVideo.srcObject = event.stream;
        }
    };

    peerConnection.setRemoteDescription(new RTCSessionDescription(offer))
        .then(() => peerConnection.createAnswer())
        .then(answer => peerConnection.setLocalDescription(answer))
        .then(() => {
            sendSignalingMessage('answer', peerConnection.localDescription);
        });
}

// Function to handle answer
function handleAnswer(answer) {
    peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
}

// Function to handle ICE candidate
function handleCandidate(candidate) {
    peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
}

// Function to send signaling message via chat
function sendSignalingMessage(type, data) {
    const message = JSON.stringify({ type, data });
    // Use existing sendMessage function or directly call API
    fetch('chat_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, message_type: 'call' })
    });
}

// Function to end call
function endCall() {
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
    }
    // Hide video elements
    const localVideo = document.getElementById('local-video');
    const remoteVideo = document.getElementById('remote-video');
    if (localVideo) localVideo.srcObject = null;
    if (remoteVideo) remoteVideo.srcObject = null;
}

// Listen for signaling messages (this would be integrated with chat polling)
function checkForSignalingMessages(messages) {
    messages.forEach(message => {
        if (message.message_type === 'call') {
            const signaling = JSON.parse(message.message);
            switch (signaling.type) {
                case 'offer':
                    showCallNotification(signaling.data);
                    break;
                case 'answer':
                    handleAnswer(signaling.data);
                    break;
                case 'candidate':
                    handleCandidate(signaling.data);
                    break;
            }
        }
    });
}

// Function to show call notification
function showCallNotification(offer) {
    const notification = document.getElementById('call-notification');
    if (notification) {
        notification.style.display = 'block';
        notification.innerHTML = `
            <div class="call-notification-content">
                <p>Incoming ${offer.type === 'offer' ? 'call' : 'video call'} from another user.</p>
                <button id="accept-call">Accept</button>
                <button id="decline-call">Decline</button>
            </div>
        `;

        document.getElementById('accept-call').addEventListener('click', () => {
            notification.style.display = 'none';
            handleOffer(offer);
        });

        document.getElementById('decline-call').addEventListener('click', () => {
            notification.style.display = 'none';
            // Optionally send a decline message
        });
    }
}

// Make functions global for access from other scripts
window.startCall = startCall;
window.checkForSignalingMessages = checkForSignalingMessages;

<?php
require_once dirname(__DIR__) . '/app/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firebase Test</title>
    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js';
        import { getAuth, GoogleAuthProvider, signInWithPopup } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js';

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyD_8AtkQDfW_QaDNco2CJqMUTxbC5HbStU",
            authDomain: null,
            projectId: "studio-3044054056-b37d9",
            storageBucket: "studio-3044054056-b37d9.firebasestorage.app",
            messagingSenderId: "215339299532",
            appId: "1:215339299532:web:aab8bcc7ce99cb8a580869"
        };

        console.log('Initializing Firebase...');
        const app = initializeApp(firebaseConfig);
        console.log('Firebase initialized successfully');

        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();
        provider.addScope('email');
        provider.addScope('profile');

        console.log('Firebase Auth initialized');

        // Test popup function
        window.testFirebasePopup = async function() {
            try {
                console.log('Testing Firebase auth with popup...');
                const result = await signInWithPopup(auth, provider);
                console.log('Auth successful:', result.user);
                alert('Firebase auth successful! User: ' + result.user.email);
            } catch (error) {
                console.error('Firebase auth error:', error);
                alert('Firebase auth failed: ' + error.message + ' (Code: ' + error.code + ')');
            }
        };

        // Test redirect function
        window.testFirebaseRedirect = async function() {
            try {
                console.log('Testing Firebase auth with redirect...');
                await signInWithRedirect(auth, provider);
            } catch (error) {
                console.error('Firebase redirect error:', error);
                alert('Firebase redirect failed: ' + error.message + ' (Code: ' + error.code + ')');
            }
        };

        // Check for redirect result
        window.checkRedirectResult = async function() {
            try {
                console.log('Checking redirect result...');
                const result = await getRedirectResult(auth);
                if (result) {
                    console.log('Redirect result:', result.user);
                    alert('Redirect auth successful! User: ' + result.user.email);
                } else {
                    console.log('No redirect result');
                    alert('No redirect result found');
                }
            } catch (error) {
                console.error('Redirect result error:', error);
                alert('Redirect result error: ' + error.message + ' (Code: ' + error.code + ')');
            }
        };
    </script>
</head>
<body>
    <h1>Firebase Test</h1>
    <button onclick="testFirebasePopup()">Test Firebase Auth (Popup)</button>
    <button onclick="testFirebaseRedirect()">Test Firebase Auth (Redirect)</button>
    <button onclick="checkRedirectResult()">Check Redirect Result</button>
    <div id="status"></div>
    <script>
        console.log('Page loaded, Firebase should be initializing...');
    </script>
</body>
</html>

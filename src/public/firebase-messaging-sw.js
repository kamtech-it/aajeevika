importScripts("https://www.gstatic.com/firebasejs/7.16.1/firebase-app.js");
importScripts(
    "https://www.gstatic.com/firebasejs/7.16.1/firebase-messaging.js",
);
// For an optimal experience using Cloud Messaging, also add the Firebase SDK for Analytics.
importScripts(
    "https://www.gstatic.com/firebasejs/7.16.1/firebase-analytics.js",
);

// Initialize the Firebase app in the service worker by passing in the
// messagingSenderId.
firebase.initializeApp({
    messagingSenderId: "366046427858",
    apiKey: "BImHvRQ0B3LbLDZeD54c4B6ozsVSvqappFWG_cia07bJf_nLAsNkiBH7mI9XKVpVSYIDyPEwW49u0Vo1iRnxhgo",
    projectId: "undp-26448",
    appId: "1:366046427858:web:009b1814c672749d4b8b0b",
});

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function (payload) {
    console.log(
        "[firebase-messaging-sw.js] Received background message ",
        payload,
    );
    // Customize notification here
    const notificationTitle = "Background Message Title";
    const notificationOptions = {
        body: "Background Message body.",
        icon: "https://undp.svaptech.tk/public/assets/images/logo1.png",
    };

    return self.registration.showNotification(
        notificationTitle,
        notificationOptions,
    );
});

messaging.onMessage(function(payload) {

    console.log(payload.data.badgeCount);

});



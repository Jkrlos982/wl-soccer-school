import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
import App from './App';
import reportWebVitals from './reportWebVitals';
import * as serviceWorkerRegistration from './serviceWorkerRegistration';

const root = ReactDOM.createRoot(
  document.getElementById('root') as HTMLElement
);
root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: https://cra.link/PWA
serviceWorkerRegistration.register({
  onSuccess: (registration) => {
    console.log('PWA installed successfully');
    // Request notification permission
    serviceWorkerRegistration.requestNotificationPermission().then((permission) => {
      if (permission === 'granted') {
        console.log('Notification permission granted');
        // Subscribe to push notifications
        serviceWorkerRegistration.subscribeToPushNotifications(registration);
      }
    });
  },
  onUpdate: (registration) => {
    console.log('New content available, please refresh');
    // You can show a notification to the user here
  },
});

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
reportWebVitals();

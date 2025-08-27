// PWA Utilities for WL School Management System

/**
 * Check if the app is running as a PWA
 */
export const isPWA = (): boolean => {
  return (
    window.matchMedia('(display-mode: standalone)').matches ||
    (window.navigator as any).standalone === true ||
    document.referrer.includes('android-app://')
  );
};

/**
 * Check if the app can be installed (PWA prompt available)
 */
export const canInstallPWA = (): boolean => {
  return 'beforeinstallprompt' in window;
};

/**
 * Show PWA install prompt
 */
export const showInstallPrompt = async (): Promise<boolean> => {
  if ('beforeinstallprompt' in window) {
    const event = (window as any).deferredPrompt;
    if (event) {
      event.prompt();
      const { outcome } = await event.userChoice;
      return outcome === 'accepted';
    }
  }
  return false;
};

/**
 * Check if device is online
 */
export const isOnline = (): boolean => {
  return navigator.onLine;
};

/**
 * Add online/offline event listeners
 */
export const addNetworkListeners = (
  onOnline: () => void,
  onOffline: () => void
): (() => void) => {
  window.addEventListener('online', onOnline);
  window.addEventListener('offline', onOffline);

  // Return cleanup function
  return () => {
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
  };
};

/**
 * Get device information
 */
export const getDeviceInfo = () => {
  const userAgent = navigator.userAgent;
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);
  const isIOS = /iPad|iPhone|iPod/.test(userAgent);
  const isAndroid = /Android/.test(userAgent);
  
  return {
    isMobile,
    isIOS,
    isAndroid,
    isDesktop: !isMobile,
    userAgent,
    platform: navigator.platform,
    language: navigator.language,
    cookieEnabled: navigator.cookieEnabled,
    onLine: navigator.onLine
  };
};

/**
 * Check if notifications are supported and get permission status
 */
export const getNotificationStatus = (): {
  supported: boolean;
  permission: NotificationPermission;
} => {
  const supported = 'Notification' in window;
  const permission = supported ? Notification.permission : 'denied';
  
  return { supported, permission };
};

/**
 * Request notification permission
 */
export const requestNotificationPermission = async (): Promise<NotificationPermission> => {
  if ('Notification' in window) {
    return await Notification.requestPermission();
  }
  return 'denied';
};

/**
 * Show local notification
 */
export const showNotification = (
  title: string,
  options?: NotificationOptions
): Notification | null => {
  if ('Notification' in window && Notification.permission === 'granted') {
    return new Notification(title, {
      icon: '/logo192.png',
      badge: '/favicon.ico',
      ...options
    });
  }
  return null;
};

/**
 * Check if app is in fullscreen mode
 */
export const isFullscreen = (): boolean => {
  return (
    document.fullscreenElement !== null ||
    (document as any).webkitFullscreenElement !== null ||
    (document as any).mozFullScreenElement !== null ||
    (document as any).msFullscreenElement !== null
  );
};

/**
 * Toggle fullscreen mode
 */
export const toggleFullscreen = async (): Promise<void> => {
  if (isFullscreen()) {
    // Exit fullscreen
    if (document.exitFullscreen) {
      await document.exitFullscreen();
    } else if ((document as any).webkitExitFullscreen) {
      (document as any).webkitExitFullscreen();
    } else if ((document as any).mozCancelFullScreen) {
      (document as any).mozCancelFullScreen();
    } else if ((document as any).msExitFullscreen) {
      (document as any).msExitFullscreen();
    }
  } else {
    // Enter fullscreen
    const element = document.documentElement;
    if (element.requestFullscreen) {
      await element.requestFullscreen();
    } else if ((element as any).webkitRequestFullscreen) {
      (element as any).webkitRequestFullscreen();
    } else if ((element as any).mozRequestFullScreen) {
      (element as any).mozRequestFullScreen();
    } else if ((element as any).msRequestFullscreen) {
      (element as any).msRequestFullscreen();
    }
  }
};

/**
 * Get app version from package.json or environment
 */
export const getAppVersion = (): string => {
  return process.env.REACT_APP_VERSION || '1.0.0';
};

/**
 * Check if app needs update (compare versions)
 */
export const checkForUpdates = async (): Promise<boolean> => {
  try {
    // In a real app, you would check against your API or version endpoint
    const response = await fetch('/version.json');
    if (response.ok) {
      const { version } = await response.json();
      return version !== getAppVersion();
    }
  } catch (error) {
    console.warn('Could not check for updates:', error);
  }
  return false;
};

/**
 * Store data in localStorage with error handling
 */
export const setLocalStorage = (key: string, value: any): boolean => {
  try {
    localStorage.setItem(key, JSON.stringify(value));
    return true;
  } catch (error) {
    console.error('Failed to save to localStorage:', error);
    return false;
  }
};

/**
 * Get data from localStorage with error handling
 */
export const getLocalStorage = <T>(key: string, defaultValue?: T): T | null => {
  try {
    const item = localStorage.getItem(key);
    return item ? JSON.parse(item) : defaultValue || null;
  } catch (error) {
    console.error('Failed to read from localStorage:', error);
    return defaultValue || null;
  }
};

/**
 * Remove data from localStorage
 */
export const removeLocalStorage = (key: string): boolean => {
  try {
    localStorage.removeItem(key);
    return true;
  } catch (error) {
    console.error('Failed to remove from localStorage:', error);
    return false;
  }
};

/**
 * Clear all localStorage data
 */
export const clearLocalStorage = (): boolean => {
  try {
    localStorage.clear();
    return true;
  } catch (error) {
    console.error('Failed to clear localStorage:', error);
    return false;
  }
};
/**
 * session.ts — Utility to generate/retrieve a persistent anonymous session ID for tracking
 *
 * Requirements:
 * - GDPR/LOPDGDD Compliant: No personal identifying data used.
 * - Alphanumeric hash >= 32 characters to pass backend validation.
 */

export function getSessionId(): string {
  if (typeof window === 'undefined') {
    return 'server_session_placeholder_value_32';
  }
  let sid = sessionStorage.getItem('datanestiq_session_id');
  if (!sid) {
    const arr = new Uint8Array(16);
    if (window.crypto && window.crypto.getRandomValues) {
      window.crypto.getRandomValues(arr);
    } else {
      for (let i = 0; i < 16; i++) {
        arr[i] = Math.floor(Math.random() * 256);
      }
    }
    sid = Array.from(arr, dec => dec.toString(16).padStart(2, '0')).join('');
    sessionStorage.setItem('datanestiq_session_id', sid);
  }
  return sid;
}

/**
 * Sends a fire-and-forget telemetric event to the database.
 * Does not block main thread. Fails silently.
 */
export function trackEvent(type: string, target: string, value: string): void {
  try {
    const sessionId = getSessionId();
    const payload = JSON.stringify({
      session_id: sessionId,
      event_type: type,
      event_target: target,
      event_value: value
    });

    const url = '/api/track_event.php';
    if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
      const blob = new Blob([payload], { type: 'application/json' });
      navigator.sendBeacon(url, blob);
    } else {
      // Fallback
      fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: payload
      }).catch(() => {});
    }
  } catch (e) {
    // Fail silently - 0 UX degradation
  }
}

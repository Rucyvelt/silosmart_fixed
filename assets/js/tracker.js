/**
 * SiloSmart – Activity Tracker & Security Module
 * Handles: background camera snapshots, GPS, device fingerprint,
 * and forensic activity logging to /api/activity/log.php
 */
(function (SS) {
  'use strict';

  // ─── CONFIG ───────────────────────────────────────────────────
  SS.config = {
    apiEndpoint: '/api/activity/log.php',
    snapshotEnabled: true,
    gpsEnabled: true,
    fingerprintEnabled: true,
    snapshotQuality: 0.6,
    snapshotWidth: 320,
    snapshotHeight: 240,
    highSecurityActions: [
      'login', 'logout', 'payment_initiated', 'report_exported',
      'alert_acknowledged', 'user_added', 'user_removed',
      'silo_deleted', 'settings_changed', 'password_changed'
    ]
  };

  // ─── STATE ────────────────────────────────────────────────────
  SS._stream    = null;
  SS._canvas    = null;
  SS._geo       = null;
  SS._fp        = null;
  SS._camReady  = false;

  // ─── INIT ─────────────────────────────────────────────────────
  SS.init = function () {
    SS._buildCanvas();
    SS._initFingerprint();
    if (SS.config.gpsEnabled) SS._requestGPS();
    if (SS.config.snapshotEnabled) SS._requestCamera();
    SS._attachFormTracking();
    SS._attachNavTracking();
    console.log('[SiloSmart Tracker] Initialized');
  };

  // ─── CANVAS SETUP ─────────────────────────────────────────────
  SS._buildCanvas = function () {
    SS._canvas = document.createElement('canvas');
    SS._canvas.width  = SS.config.snapshotWidth;
    SS._canvas.height = SS.config.snapshotHeight;
    SS._canvas.style.display = 'none';
    document.body.appendChild(SS._canvas);
  };

  // ─── DEVICE FINGERPRINT ───────────────────────────────────────
  SS._initFingerprint = function () {
    const parts = [
      navigator.userAgent,
      navigator.language,
      screen.width + 'x' + screen.height + 'x' + screen.colorDepth,
      new Date().getTimezoneOffset(),
      navigator.hardwareConcurrency || 0,
      navigator.maxTouchPoints || 0,
      (navigator.plugins || []).length,
    ];
    SS._fp = SS._simpleHash(parts.join('|'));
  };

  SS._simpleHash = function (str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const c = str.charCodeAt(i);
      hash = (hash << 5) - hash + c;
      hash |= 0;
    }
    return Math.abs(hash).toString(16);
  };

  // ─── GPS ──────────────────────────────────────────────────────
  SS._requestGPS = function () {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        SS._geo = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      },
      function () { SS._geo = null; },
      { timeout: 8000, maximumAge: 300000 }
    );
  };

  // ─── CAMERA ───────────────────────────────────────────────────
  SS._requestCamera = function () {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
    navigator.mediaDevices.getUserMedia({
      video: {
        width:  { ideal: SS.config.snapshotWidth },
        height: { ideal: SS.config.snapshotHeight },
        facingMode: 'user'
      }
    }).then(function (stream) {
      SS._stream   = stream;
      SS._camReady = true;
    }).catch(function (err) {
      console.warn('[SiloSmart Tracker] Camera unavailable:', err.name);
      SS._camReady = false;
    });
  };

  // ─── CAPTURE SNAPSHOT ─────────────────────────────────────────
  SS._captureSnapshot = function (callback) {
    if (!SS._camReady || !SS._stream || !SS._canvas) {
      callback(null);
      return;
    }
    const video = document.createElement('video');
    video.srcObject = SS._stream;
    video.muted     = true;
    video.playsInline = true;
    video.oncanplay = function () {
      video.play();
      setTimeout(function () {
        const ctx = SS._canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, SS._canvas.width, SS._canvas.height);
        const dataUrl = SS._canvas.toDataURL('image/jpeg', SS.config.snapshotQuality);
        video.pause();
        video.srcObject = null;
        callback(dataUrl);
      }, 200);
    };
    video.onerror = function () { callback(null); };
  };

  // ─── LOG ACTION ───────────────────────────────────────────────
  SS.log = function (action, category, description, entityType, entityId) {
    var isHighSecurity = SS.config.highSecurityActions.includes(action);
    var payload = {
      action:       action,
      category:     category || 'system',
      description:  description || '',
      entity_type:  entityType || null,
      entity_id:    entityId || null,
      fingerprint:  SS._fp,
      location:     SS._geo,
    };

    if (isHighSecurity && SS._camReady) {
      SS._captureSnapshot(function (snapshot) {
        payload.snapshot = snapshot;
        SS._send(payload);
      });
    } else {
      SS._send(payload);
    }
  };

  // ─── SEND TO API ──────────────────────────────────────────────
  SS._send = function (payload) {
    const body = JSON.stringify(payload);

    // Use sendBeacon for guaranteed delivery (e.g., on page unload)
    if (navigator.sendBeacon) {
      const blob = new Blob([body], { type: 'application/json' });
      navigator.sendBeacon(SS.config.apiEndpoint, blob);
    } else {
      fetch(SS.config.apiEndpoint, {
        method:      'POST',
        headers:     { 'Content-Type': 'application/json' },
        body:        body,
        credentials: 'same-origin',
        keepalive:   true,
      }).catch(function (e) {
        console.warn('[SiloSmart Tracker] Log failed:', e.message);
      });
    }
  };

  // ─── AUTO TRACKING ────────────────────────────────────────────
  SS._attachFormTracking = function () {
    document.addEventListener('submit', function (e) {
      const form = e.target;
      const action = form.dataset.ssAction || 'form_submit';
      const category = form.dataset.ssCategory || 'system';
      SS.log(action, category, 'Form submitted: ' + (form.id || form.action));
    }, true);
  };

  SS._attachNavTracking = function () {
    // Track SPA navigation
    const originalPushState = history.pushState;
    history.pushState = function () {
      originalPushState.apply(this, arguments);
      SS.log('page_view', 'system', 'Navigated to: ' + location.pathname);
    };
  };

  // ─── HIGH-LEVEL HELPERS ───────────────────────────────────────
  SS.trackLogin     = function () { SS.log('login', 'auth', 'User logged in'); };
  SS.trackLogout    = function () { SS.log('logout', 'auth', 'User logged out'); };
  SS.trackPayment   = function (desc) { SS.log('payment_initiated', 'payment', desc); };
  SS.trackAlert     = function (alertId) { SS.log('alert_acknowledged', 'alert', 'Alert acknowledged', 'alert', alertId); };
  SS.trackReport    = function (type) { SS.log('report_exported', 'report', 'Exported: ' + type); };
  SS.trackSiloView  = function (siloId) { SS.log('silo_viewed', 'silo', 'Silo detail viewed', 'silo', siloId); };
  SS.trackTask      = function (taskId, action) { SS.log('task_' + action, 'task', 'Task ' + action, 'task', taskId); };
  SS.trackSensor    = function (siloId) { SS.log('sensor_reading_added', 'sensor', 'Manual reading entered', 'silo', siloId); };

  // ─── PAGE UNLOAD ─────────────────────────────────────────────
  window.addEventListener('beforeunload', function () {
    SS.log('page_exit', 'system', 'Left page: ' + location.pathname);
    // Stop camera stream
    if (SS._stream) {
      SS._stream.getTracks().forEach(function (t) { t.stop(); });
    }
  });

})(window.SiloSmartTracker = window.SiloSmartTracker || {});

// ─── AUTO INIT ON LOAD ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  SiloSmartTracker.init();
  // Track initial page load
  SiloSmartTracker.log('page_view', 'system', 'Page loaded: ' + document.title);
});

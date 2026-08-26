<?php
// Session configuration — include this on every page BEFORE session_start()
ini_set('session.cookie_lifetime', 30 * 24 * 60 * 60); // 30 days
ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);  // 30 days
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

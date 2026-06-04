<?php
/**
 * Security headers — require this at the top of every file that produces output,
 * before any HTML is emitted. (M-04)
 */
header("Content-Security-Policy: default-src 'self'; style-src 'self'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("X-XSS-Protection: 1; mode=block");

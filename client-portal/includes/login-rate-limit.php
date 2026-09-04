<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Progressive rate limiting for the custom publisher login
 * (/publisher-login/) only. Native WordPress authentication (wp-login.php)
 * is untouched.
 *
 * Tracking is layered, per CLAUDE.md's guidance that neither username nor
 * IP alone is a safe key (bots rotate usernames; IPs are sometimes shared):
 *  - a primary "identifier + IP" key gets the full progressive escalation
 *    (3 fails -> 1 min, 6 -> 5 min, 9 -> 30 min, 12+ -> 3 hours), and
 *  - a looser IP-only guard catches an attacker rotating usernames from the
 *    same address, independent of which identifier is being tried.
 * Keys are SHA-256 hashes of the identifier/IP, never stored in plaintext.
 * State lives in transients (object-cache compatible, self-expiring - no
 * permanent table to grow or clean up).
 */

define('CP_LOGIN_RATE_LIMIT_ATTEMPT_THRESHOLD', 3);
define('CP_LOGIN_RATE_LIMIT_FAIL_WINDOW', HOUR_IN_SECONDS);
define('CP_LOGIN_RATE_LIMIT_ESCALATION_TTL', DAY_IN_SECONDS);
define('CP_LOGIN_RATE_LIMIT_IP_GUARD_THRESHOLD', 15);
define('CP_LOGIN_RATE_LIMIT_IP_GUARD_WINDOW', HOUR_IN_SECONDS);
define('CP_LOGIN_RATE_LIMIT_IP_GUARD_LOCK', 5 * MINUTE_IN_SECONDS);

function cp_login_rate_limit_lock_durations()
{
    return [MINUTE_IN_SECONDS, 5 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS, 3 * HOUR_IN_SECONDS];
}

/**
 * The connecting peer address as the server/PHP itself observed it.
 * Deliberately ignores client-suppliable headers like X-Forwarded-For,
 * which any visitor can set to an arbitrary value; this environment has no
 * already-trusted reverse-proxy mechanism to validate such a header against.
 */
function cp_login_rate_limit_client_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR'])
        ? (string) $_SERVER['REMOTE_ADDR']
        : '';
}

function cp_login_rate_limit_normalize_identifier($login)
{
    return strtolower(trim((string) $login));
}

function cp_login_rate_limit_identifier_key($login)
{
    return hash('sha256', 'id|' . cp_login_rate_limit_normalize_identifier($login) . '|' . cp_login_rate_limit_client_ip());
}

function cp_login_rate_limit_ip_key()
{
    return hash('sha256', 'ip|' . cp_login_rate_limit_client_ip());
}

function cp_login_rate_limit_locked_until($key)
{
    $locked_until = absint(get_transient('cp_llr_lock_' . $key));
    return $locked_until > time() ? $locked_until : 0;
}

/**
 * Returns the Unix timestamp the login is locked until (for the more
 * restrictive of the identifier+IP or IP-only guard), or 0 if not locked.
 */
function cp_login_rate_limit_is_locked($login)
{
    if ('' === cp_login_rate_limit_client_ip()) {
        return 0;
    }

    $identifier_lock = cp_login_rate_limit_locked_until(cp_login_rate_limit_identifier_key($login));
    $ip_lock = cp_login_rate_limit_locked_until(cp_login_rate_limit_ip_key());

    return max($identifier_lock, $ip_lock);
}

function cp_login_rate_limit_bump_identifier($key)
{
    $fail_count = absint(get_transient('cp_llr_fails_' . $key)) + 1;
    set_transient('cp_llr_fails_' . $key, $fail_count, CP_LOGIN_RATE_LIMIT_FAIL_WINDOW);

    if (0 !== $fail_count % CP_LOGIN_RATE_LIMIT_ATTEMPT_THRESHOLD) {
        return;
    }

    $durations = cp_login_rate_limit_lock_durations();
    $level = min(absint(get_transient('cp_llr_level_' . $key)) + 1, count($durations));
    set_transient('cp_llr_level_' . $key, $level, CP_LOGIN_RATE_LIMIT_ESCALATION_TTL);

    $duration = $durations[$level - 1];
    set_transient('cp_llr_lock_' . $key, time() + $duration, $duration);
}

function cp_login_rate_limit_bump_ip_guard($key)
{
    $fail_count = absint(get_transient('cp_llr_fails_' . $key)) + 1;
    set_transient('cp_llr_fails_' . $key, $fail_count, CP_LOGIN_RATE_LIMIT_IP_GUARD_WINDOW);

    if ($fail_count < CP_LOGIN_RATE_LIMIT_IP_GUARD_THRESHOLD) {
        return;
    }

    set_transient('cp_llr_lock_' . $key, time() + CP_LOGIN_RATE_LIMIT_IP_GUARD_LOCK, CP_LOGIN_RATE_LIMIT_IP_GUARD_LOCK);
}

/**
 * Record one failed authentication submission. Only call this after
 * wp_signon() itself returned a WP_Error - never for page views, nonce
 * failures, or empty-field submissions that never attempted a password.
 */
function cp_login_rate_limit_record_failure($login)
{
    if ('' === cp_login_rate_limit_client_ip()) {
        return;
    }

    cp_login_rate_limit_bump_identifier(cp_login_rate_limit_identifier_key($login));
    cp_login_rate_limit_bump_ip_guard(cp_login_rate_limit_ip_key());
}

/**
 * Clear the consecutive-failure state for this identifier after a
 * successful login. The shared IP-guard counter is deliberately left alone:
 * one valid login from an IP does not prove other identifiers are not still
 * under attack from the same address.
 */
function cp_login_rate_limit_clear($login)
{
    $identifier_key = cp_login_rate_limit_identifier_key($login);
    delete_transient('cp_llr_fails_' . $identifier_key);
    delete_transient('cp_llr_level_' . $identifier_key);
    delete_transient('cp_llr_lock_' . $identifier_key);
}

/**
 * Generic lockout message - identical regardless of whether the attempted
 * account exists, so lockout state itself never reveals account existence.
 */
function cp_login_rate_limit_locked_message($locked_until)
{
    return sprintf(
        /* translators: %s: human-readable remaining wait time, e.g. "5 minutes". */
        __('Too many login attempts. Please try again in %s.', 'client-portal'),
        human_time_diff(time(), max(time() + 1, absint($locked_until)))
    );
}

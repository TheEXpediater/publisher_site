<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate limiting for the custom publisher login (/publisher-login/) only.
 * Native WordPress authentication (wp-login.php) is untouched.
 *
 * Two independent limiters, each with its own failure count/level/lock:
 *  - ACCOUNT: keyed only by the canonical account being attempted (never
 *    the client's address). This is what actually stops repeated wrong
 *    passwords against one account, and it must keep counting correctly
 *    even when the observed client address is not stable between requests
 *    - proven on the live Hostinger runtime, where consecutive requests
 *      from the same real visitor did not reliably share the same
 *      REMOTE_ADDR, which silently reset an IP-mixed-in key before it
 *      ever reached the 3-failure threshold.
 *  - SOURCE: keyed only by the connecting address, as a separate
 *    defense-in-depth guard against an attacker rotating fake usernames
 *    from one address. Independent on purpose - see CP_LOGIN_RATE_LIMIT_IP_GUARD_*
 *    below - so it is never the reason an account-only lock fails to
 *    trigger, and vice versa.
 * A login is blocked whenever either limiter says so; cp_login_rate_limit_is_locked()
 * returns the later of the two expirations so the UI never claims login is
 * available before the server will actually allow it.
 *
 * Keys are always HMAC-SHA256 (WordPress's own auth salt, so they cannot be
 * precomputed from a guessed email/IP without server access), never a raw
 * or plain-hashed identifier. State lives in transients (object-cache
 * compatible, self-expiring - no permanent table to grow or clean up).
 */

define('CP_LOGIN_RATE_LIMIT_ATTEMPT_THRESHOLD', 3);
define('CP_LOGIN_RATE_LIMIT_FAIL_WINDOW', HOUR_IN_SECONDS);
define('CP_LOGIN_RATE_LIMIT_ESCALATION_TTL', DAY_IN_SECONDS);
define('CP_LOGIN_RATE_LIMIT_IP_GUARD_THRESHOLD', 15);
define('CP_LOGIN_RATE_LIMIT_IP_GUARD_WINDOW', HOUR_IN_SECONDS);
define('CP_LOGIN_RATE_LIMIT_IP_GUARD_LOCK', 5 * MINUTE_IN_SECONDS);

/**
 * Lock durations by escalation level for the ACCOUNT limiter. The first
 * lock (3rd consecutive failure, level 1) is 5 minutes per the Publisher
 * Portal security requirement.
 */
function cp_login_rate_limit_lock_durations()
{
    return [5 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS, 3 * HOUR_IN_SECONDS, 24 * HOUR_IN_SECONDS];
}

/**
 * The connecting peer address as the server/PHP itself observed it.
 * Deliberately ignores client-suppliable headers like X-Forwarded-For,
 * which any visitor can set to an arbitrary value; this environment has no
 * proven-trustworthy reverse-proxy configuration to validate such a header
 * against. Used only for the separate SOURCE limiter below - the ACCOUNT
 * limiter never factors this in.
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

/**
 * Resolves a submitted login field (username or email) to a stable
 * canonical value for the ACCOUNT limiter, so "wrong password with the
 * username" and "wrong password with the email" against the same
 * WordPress account count against the same limiter instead of two
 * separate ones. get_user_by() is used only to derive this internal
 * limiter key - never to decide or reveal whether the account exists in
 * any response the client can observe; the login error message stays
 * generic either way (see cp_process_custom_login()), and an identifier
 * that resolves to no account still gets a stable "raw:" key of its own so
 * the limiter works identically against attackers guessing at usernames
 * that were never registered.
 */
function cp_login_rate_limit_canonical_account_value($login)
{
    $normalized = cp_login_rate_limit_normalize_identifier($login);
    if ('' === $normalized) {
        return '';
    }

    $user = is_email($normalized) ? get_user_by('email', $normalized) : false;
    if (!$user instanceof WP_User) {
        $user = get_user_by('login', $normalized);
    }

    return $user instanceof WP_User ? ('uid:' . $user->ID) : ('raw:' . $normalized);
}

/**
 * ACCOUNT limiter key - the canonical account value only, never the client
 * address, so this stays stable across requests regardless of how the
 * visitor's observed IP behaves on this host. HMAC'd with WordPress's own
 * auth salt (wp_salt()) rather than a plain hash, so the key can't be
 * precomputed for a guessed email/username without server-side secrets.
 */
function cp_login_rate_limit_account_key($login)
{
    return hash_hmac('sha256', 'account:' . cp_login_rate_limit_canonical_account_value($login), wp_salt('auth'));
}

/**
 * SOURCE limiter key - the connecting address only, independent of which
 * account is being attempted. Kept as its own limiter (not merged back
 * into the account key) so an unstable/shared address can never suppress
 * the account limiter, and a genuinely malicious single address rotating
 * through many fake usernames is still caught on its own terms.
 */
function cp_login_rate_limit_source_key()
{
    return hash_hmac('sha256', 'source:' . cp_login_rate_limit_client_ip(), wp_salt('auth'));
}

function cp_login_rate_limit_locked_until($key)
{
    $locked_until = absint(get_transient('cp_llr_lock_' . $key));
    return $locked_until > time() ? $locked_until : 0;
}

/**
 * Returns the Unix timestamp login is locked until (the later of the
 * account lock or the source lock), or 0 if neither applies.
 */
function cp_login_rate_limit_is_locked($login)
{
    $account_lock = cp_login_rate_limit_locked_until(cp_login_rate_limit_account_key($login));

    $source_lock = 0;
    if ('' !== cp_login_rate_limit_client_ip()) {
        $source_lock = cp_login_rate_limit_locked_until(cp_login_rate_limit_source_key());
    }

    return max($account_lock, $source_lock);
}

function cp_login_rate_limit_bump_account($key)
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
    $locked_until = time() + $duration;
    set_transient('cp_llr_lock_' . $key, $locked_until, $duration);

    cp_debug_log('Login rate limiter: account lock created', [
        'fail_count' => $fail_count,
        'level' => $level,
        'duration_seconds' => $duration,
        'locked_until' => $locked_until,
    ]);
}

function cp_login_rate_limit_bump_source($key)
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
 * Bumps the account and source limiters independently; either can trigger
 * a lock without the other being involved.
 */
function cp_login_rate_limit_record_failure($login)
{
    cp_login_rate_limit_bump_account(cp_login_rate_limit_account_key($login));

    if ('' !== cp_login_rate_limit_client_ip()) {
        cp_login_rate_limit_bump_source(cp_login_rate_limit_source_key());
    }
}

/**
 * Clear the consecutive-failure state for this account after a successful
 * login. The separate source-limiter counter is deliberately left alone:
 * one valid login from an address does not prove other accounts are not
 * still under attack from the same address.
 */
function cp_login_rate_limit_clear($login)
{
    $account_key = cp_login_rate_limit_account_key($login);
    delete_transient('cp_llr_fails_' . $account_key);
    delete_transient('cp_llr_level_' . $account_key);
    delete_transient('cp_llr_lock_' . $account_key);
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

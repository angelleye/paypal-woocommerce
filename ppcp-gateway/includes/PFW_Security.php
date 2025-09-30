<?php
// File: includes/class-pfw-security.php  (adjust path/namespace to your plugin)

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PFW_Security {

    const OPTION_SECRET     = 'pfw_action_token_secret';
    const TRANSIENT_PREFIX  = 'pfw_atk_';         // replay/jti cache
    const DEFAULT_TTL       = 600;                // 10 minutes
    const REPLAY_TTL        = 900;                // 15 minutes jti memory

    /** Ensure we have a persistent secret */
    public static function get_secret(): string {
        $secret = get_option( self::OPTION_SECRET );
        if ( empty( $secret ) ) {
            $secret = bin2hex( random_bytes( 32 ) );  // 64 hex chars
            update_option( self::OPTION_SECRET, $secret, false );
        }
        return (string) $secret;
    }

    /** Stable per-visitor session key (WC session id preferred) */
    public static function get_session_key(): string {
        if ( function_exists( 'WC' ) && WC()->session && WC()->session->get_session_cookie() ) {
            $cookie = WC()->session->get_session_cookie();
            if ( is_array( $cookie ) && ! empty( $cookie[0] ) ) {
                return 'wc_' . preg_replace( '/[^a-z0-9_]/i', '', $cookie[0] );
            }
        }
        // fallback cookie
        if ( isset( $_COOKIE['pfw_sess'] ) && preg_match( '/^[a-f0-9]{40}$/', $_COOKIE['pfw_sess'] ) ) {
            return 'ck_' . $_COOKIE['pfw_sess'];
        }
        $k = bin2hex( random_bytes( 20 ) );
        setcookie( 'pfw_sess', $k, time() + 30*DAY_IN_SECONDS, '/', '', is_ssl(), true );
        $_COOKIE['pfw_sess'] = $k;
        return 'ck_' . $k;
    }

    /** WooCommerce cart hash (built-in) */
    public static function get_cart_hash(): string {
        if ( function_exists( 'WC' ) && WC()->cart ) {
            // get_cart_hash() exists on WC_Cart
            $h = WC()->cart->get_cart_hash();
            return is_string( $h ) ? $h : '';
        }
        return '';
    }

    /** base64url helpers */
    protected static function b64u_encode( string $s ): string {
        return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' );
    }
    protected static function b64u_decode( string $s ): string {
        return base64_decode( strtr( $s, '-_', '+/' ) );
    }

    /**
     * Issue an action token.
     * @param string $action   e.g. 'create_order' or 'cc_capture'
     * @param int    $ttl      seconds (default 10m)
     * @param array  $extra    extra claims (e.g., paypal_order_id)
     * @return string token
     */
    public static function issue_action_token( string $action, int $ttl = self::DEFAULT_TTL, array $extra = [] ): string {
        $claims = array_merge( [
            'act' => $action,
            'sk'  => self::get_session_key(),
            'ch'  => self::get_cart_hash(),
            'iat' => time(),
            'exp' => time() + max( 60, $ttl ),
            'jti' => bin2hex( random_bytes( 12 ) ),
        ], $extra );

        $payload = json_encode( $claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        $sig     = hash_hmac( 'sha256', $payload, self::get_secret(), true ); // binary
        return self::b64u_encode( $payload ) . '.' . self::b64u_encode( $sig );
    }

    /**
     * Verify an action token for the expected action.
     * Validates signature, expiry, session key, cart hash, anti-replay jti.
     * Optionally, you can pass required extra keys to assert equality.
     *
     * @throws Exception on any validation failure
     * @return array validated claims
     */
    public static function verify_action_token( string $token, string $expected_action, array $require_equals = [] ): array {
        if ( empty( $token ) || strpos( $token, '.' ) === false ) {
            throw new Exception( 'invalid_token_format' );
        }
        list( $p64, $s64 ) = explode( '.', $token, 2 );
        $payload = self::b64u_decode( $p64 );
        $sig     = self::b64u_decode( $s64 );

        if ( ! $payload || ! $sig ) {
            throw new Exception( 'invalid_token_encoding' );
        }

        $calc = hash_hmac( 'sha256', $payload, self::get_secret(), true );
        if ( ! hash_equals( $calc, $sig ) ) {
            throw new Exception( 'bad_signature' );
        }

        $claims = json_decode( $payload, true );
        if ( ! is_array( $claims ) ) {
            throw new Exception( 'bad_payload' );
        }

        // exp/iat
        if ( empty( $claims['exp'] ) || time() > intval( $claims['exp'] ) ) {
            throw new Exception( 'token_expired' );
        }
        if ( empty( $claims['iat'] ) || intval( $claims['iat'] ) > time() + 60 ) {
            throw new Exception( 'bad_iat' );
        }

        // action
        if ( ! isset( $claims['act'] ) || $claims['act'] !== $expected_action ) {
            throw new Exception( 'wrong_action' );
        }

        // session binding
        if ( empty( $claims['sk'] ) || $claims['sk'] !== self::get_session_key() ) {
            throw new Exception( 'session_mismatch' );
        }

        // cart-hash binding
        $current_ch = self::get_cart_hash();
        if ( empty( $claims['ch'] ) || $claims['ch'] !== $current_ch ) {
            throw new Exception( 'cart_changed' );
        }

        // require equality on extra fields (e.g., paypal_order_id on capture)
        foreach ( $require_equals as $k => $v ) {
            if ( ! array_key_exists( $k, $claims ) || strval( $claims[$k] ) !== strval( $v ) ) {
                throw new Exception( 'claim_mismatch_' . $k );
            }
        }

        // anti-replay (single-use jti)
        if ( empty( $claims['jti'] ) ) {
            throw new Exception( 'missing_jti' );
        }
        $replay_key = self::TRANSIENT_PREFIX . $claims['jti'];
        if ( get_transient( $replay_key ) ) {
            throw new Exception( 'replay_detected' );
        }
        // mark as seen
        set_transient( $replay_key, 1, self::REPLAY_TTL );

        return $claims;
    }
}

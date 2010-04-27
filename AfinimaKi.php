<?php
/**
 * @name AfinimaKi API
 * @author CinemaKi.com
 * @version 0.2
 */

include("phpxmlrpc/xmlrpc.inc");

define ('KEY_LENGTH', 32);
define ('TIME_SHIFT', 10);

class AfinimaKi {

    protected $self = array();

    /**
     * Constructor method
     *
     * @param array $itms
     * @access public
     */
    public function __construct( $itms ) {
        foreach( $itms as $name => $enum )
            $this->add($name, $enum);

        try {
            if (!$this->api_key || !$this->api_secret) {
                throw new Exception('api_key and api_secret parameters are mandatory.');
            }

            if (strlen($this->api_key) != KEY_LENGTH) {
                throw new Exception('Bad api_key ' . $this->api_key . ': it must be ' . KEY_LENGTH . ' character long.');
            }

            if (strlen($this->api_secret) != KEY_LENGTH) {
                throw new Exception('Bad api_secret ' . $this->api_secret . ': it must be ' . KEY_LENGTH . ' character long.');
            }
        } catch (Exception $e) {
            echo 'API exception: ',  $e->getMessage(), "\n";
        }

    }

    /**
     * Add elements to associative array
     *
     * @param string $name
     * @param int $enum
     * @access private
     */
    private function add( $name = null, $enum = null ) {
        if( isset($enum) )
            $this->self[$name] = $enum;
        else
            $this->self[$name] = end($this->self) + 1;
    }

    /**
     * Get value element from array
     *
     * @method __get
     * @param string $name
     * @access private
     * @return object value
     */
    private function __get( $name = null ) {
        return $this->self[$name];
    }

    /**
     * Debug method. If @param $debug is 0, no log messages. If 1, only
     * xmlrpc structure in xml format. If 2, full phpxmlrpc log messages.
     *
     * @method debug
     * @param int $debug
     * @param object $method
     * @param object $server
     * @access private
     */
    private function debug($debug, $method, $server) {
        if ($debug == 1) {
            echo "<pre>".htmlentities($method->serialize())."</pre>\n";
        }
        elseif ($debug == 2) {
            $server->setDebug(1);
        }
        else {
            $server->setDebug(0);
        }
    }

    /**
     * Authenticate code for application
     *
     * @method auth_code
     * @param string $method
     * @param string $first_arg is an user_id
     * @access private
     * @return string $code in hmac sha256
     */
    private function auth_code($method, $first_arg) {
        $code = $method . $first_arg . (int) (time() >> TIME_SHIFT);
        return hash_hmac('sha256', $code, pack("H*",$this->api_secret));
    }

    /**
     * Error exception when send request failure.
     *
     * @param object $r
     * @return true|false
     */
    public function _is_error($r) {
        if (!$r) {
            throw new Exception('Send failure.');
            return true;
        }
        elseif ($r->faultCode()) {
            if ($r->faultCode() != '-507') {
                throw new Exception('Error code: ' . $r->faultCode(). ' - ' . $r->faultString());
            }
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Send request to server. Error exception when @param fails.
     *
     * @method send_request
     * @param object $params
     * @access private
     * @return object|false
     */
    private function send_request($params) {
        // Connect to Server
        $server = new xmlrpc_client($this->path, $this->host, $this->port);

        // Debug messages
        $this->debug($this->debug, $params, $server);

        // Send request to server
        $response = $server->send($params);

        try {
            $is_error = $this->_is_error($response);
        } catch (Exception $e) {
            echo 'Method exception: ',  $e->getMessage(), "\n";
        }

        if (isset($is_error)) {
            if (!$is_error) {
                return $response->value();
            }
            else {
                return 0;
            }
        }
        else {
            return 0;
        }
    }

    /**
     * Stores a rate in the server.
     *
     * @method set_rate
     * @param string $email_sha256
     * @param int8 $user_id
     * @param int8 $item_id
     * @param double $rate
     * @param int $ts
     * @return int|false
     */
    public function set_rate($email_sha256, $item_id, $rate, $ts, $user_id = 0) {
        $params = new xmlrpcmsg(
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, "string"),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256), "string"),
                        new xmlrpcval($email_sha256, "string"),
                        new xmlrpcval($user_id, "i8"),
                        new xmlrpcval($item_id, 'i8'),
                        new xmlrpcval($rate, "double"),
                        new xmlrpcval($ts, "int")
                )
        );

        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }

    /**
     * Estimate a rate. Null is returned if the rate could not
     * be estimated (usually because the given user or the given
     * item does not have many rates).
     *
     * @method estimate_rate
     * @param string $email_sha256
     * @param int8 $item_id
     * @access public
     * @return double|false
     */
    public function estimate_rate($email_sha256, $item_id) {
        $params = new xmlrpcmsg(
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256), 'string'),
                        new xmlrpcval($email_sha256, 'string'),
                        new xmlrpcval($item_id, 'i8')
                )
        );

        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }

    /**
     * Estimate multimple rates. The returned hash has
     * the structure: item_id => estimated_rate
     *
     * @method estimate_multiple_rates
     * @param string $email_sha256
     * @param array $item_ids
     * @access public
     * @return array|null
     */
    public function estimate_multiple_rates ($email_sha256, $item_ids) {
        $params = new xmlrpcmsg(
                'estimate_multiple_rates',
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code('estimate_rate', $email_sha256), 'string'),
                        new xmlrpcval($email_sha256, 'string'),
                        new xmlrpcval($item_ids, 'array')
                )
        );
        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }

    /**
     * Get a list of user's recommentations, based on users'
     * and community previous rates.  Recommendations does not
     * include rated or marked items (in the whish or black list).
     *
     * @method get_recommendations
     * @param string $email_sha256
     * @param boolean $boolean
     * @access public
     * @return array|false
     */
    public function get_recommendations ($email_sha256, $boolean) {
        $params = new xmlrpcmsg(
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256), 'string'),
                        new xmlrpcval($email_sha256, 'string'),
                        new xmlrpcval($boolean, 'boolean')
                )
        );

        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }

    /**
     * Adds the given $item_id to user's wishlist. This
     * means that id will not be in the user's recommentation
     * list, and the action will be use to tune users's
     * recommendations (The user seems to like this item).
     *
     * @method add_to_wishlist
     * @param string $email_sha256
     * @param int8 $item_id
     * @param int $ts
     * @param int8 $user_id = 0
     * @access public
     * @return double|false
     */
    public function add_to_wishlist ($email_sha256, $item_id, $ts, $user_id = 0) {
        $params = new xmlrpcmsg (
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256), 'string'),
                        new xmlrpcval($email_sha256, 'string'),
                        new xmlrpcval($user_id, 'i8'),
                        new xmlrpcval($item_id, 'i8'),
                        new xmlrpcval($ts, 'int')
                )
        );
        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }

    /**
     * Adds the given $item_id to user's blacklist. This
     * means that id will not be in the user's recommentation
     * list, and the action will be use to tune users's
     * recommendations (The user seems to dislike this item).
     *
     * @method add_to_blacklist
     * @param string $email_sha256
     * @param int8 $item_id
     * @param int $ts
     * @param int8 $user_id = 0
     * @access public
     * @return double|false
     */
    public function add_to_blacklist ($email_sha256, $item_id, $ts, $user_id = 0) {
        $params = new xmlrpcmsg (
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256), 'string'),
                        new xmlrpcval($email_sha256, 'string'),
                        new xmlrpcval($user_id, 'i8'),
                        new xmlrpcval($item_id, 'i8'),
                        new xmlrpcval($ts, 'int')
                )
        );
        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }

    /**
     * Removes the given item from user's wish and black lists,
     * and also removes user item's rating (if any).
     *
     * @method remove_from_lists
     * @param string $email_sha256
     * @param int8 $item_id
     * @param int $ts
     * @param int8 $user_id = 0
     * @access public
     * @return double|false
     */
    public function remove_from_lists($email_sha256, $item_id, $ts, $user_id = 0) {
        $params = new xmlrpcmsg (
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256), 'string'),
                        new xmlrpcval($email_sha256, 'string'),
                        new xmlrpcval($user_id, 'i8'),
                        new xmlrpcval($item_id, 'i8'),
                        new xmlrpcval($ts, 'int')
                )
        );
        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }


    /**
     * Gets user vs user afinimaki. AfinimaKi range is [0.0-1.0].
     *
     * @method get_user_user_afinimaki
     * @param string $email_sha256_1
     * @param string $email_sha256_2
     * @access public
     * @return double|false
     */
    public function get_user_user_afinimaki($email_sha256_1, $email_sha256_2) {
        $params = new xmlrpcmsg (
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256_1), 'string'),
                        new xmlrpcval($email_sha256_1, 'string'),
                        new xmlrpcval($email_sha256_2, 'string')
                )
        );
        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }


    /**
     * Get a list of user's soul mates (users with similar
     * tastes). AfinimaKi range is [0.0-1.0].
     *
     * @method get_soul_mates
     * @param string $email_sha256
     * @access public
     * @return array|false
     */
    public function get_soul_mates($email_sha256) {
        $params = new xmlrpcmsg (
                __FUNCTION__,
                array(
                        new xmlrpcval($this->api_key, 'string'),
                        new xmlrpcval($this->auth_code(__FUNCTION__, $email_sha256), 'string'),
                        new xmlrpcval($email_sha256, 'string')
                )
        );
        $response = $this->send_request($params);

        if ($response) {
            return $response->scalarval();
        }
        else {
            return 0;
        }
    }

}

?>

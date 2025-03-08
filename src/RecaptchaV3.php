<?php
/**
 * Created by Josias Montag
 * Date: 10/30/18 11:04 AM
 * Mail: josias@montag.info
 */

namespace Lunaweb\RecaptchaV3;


use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

class RecaptchaV3
{

    /**
     * @var string
     */
    protected $secret;

    protected $secretv2;
    /**
     * @var string
     */
    protected $sitekey;
    /**
     * @var string
     */
    protected $origin;
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * RecaptchaV3 constructor.
     *
     * @param $secret
     * @param $sitekey
     */
    public function __construct(Repository $config, Client $client, Request $request, Application $app)
    {
        $this->secret = $config['recaptchav3']['secret'];
        $this->secretv2 = $config['recaptchav3']['secretv2'];
        $this->sitekey = $config['recaptchav3']['sitekey'];
        $this->sitekeyv2 = $config['recaptchav3']['sitekeyv2'];
        $this->origin = $config['recaptchav3']['origin'] ?? 'https://www.google.com/recaptcha';
        $this->locale = $config['recaptchav3']['locale'] ?? $app->getLocale();
        $this->http = $client;
        $this->request = $request;
    }


    /*
     * Verify the given token and retutn the score.
     * Returns false if token is invalid.
     * Returns the score if the token is valid.
     *
     * @param $token
     */
    public function verify($token, $action = null)
    {
        $response = $this->http->request('POST', $this->origin . '/api/siteverify', [
            'form_params' => [
                'secret'   => $this->secret,
                'response' => $token,
                'remoteip' => $this->getRealClientIp(),
            ],
        ]);


        $body = json_decode($response->getBody(), true);

        if (!isset($body['success']) || $body['success'] !== true) {
            return false;
        }

        if ($action && (!isset($body['action']) || $action != $body['action'])) {
            return false;
        }


        return isset($body['score']) ? $body['score'] : false;

    }
    public function verifyV2($token)
    {
        $response = $this->http->request('POST', $this->origin . '/api/siteverify', [
            'form_params' => [
                'secret'   => $this->secretv2,
                'response' => $token,
                'remoteip' => $this->getRealClientIp(),
            ],
        ]);

        $body = json_decode($response->getBody(), true);

        // For reCAPTCHA v2, simply check if the response indicates success.
        return isset($body['success']) && $body['success'] === true;
    }


    /**
     * @return string
     */
    public function sitekey()
    {
        return $this->sitekey;
    }
    public function sitekeyv2()
    {
        return $this->sitekeyv2;
    }

    /**
     * @return string
     */
    public function initJs()
    {
        return '<script src="' . $this->origin . '/api.js?hl=' . $this->locale . '&render=' . $this->sitekey . '"></script>';
    }

    public function initV2Js()
    {
        return '<script src="' . $this->origin . '/api.js?hl=' . $this->locale . '"></script>';
    }


    /**
     * @param $action
     */
    public function field($action, $name = 'g-recaptcha-response')
    {
        $fieldId = uniqid($name . '-', false);
        $html = '<input type="hidden" name="' . $name . '" id="' . $fieldId . '" x-ref="recaptchaToken">';
        $html .= "<script>
document.addEventListener('DOMContentLoaded', () => {
    const recaptchaInput = document.getElementById('{$fieldId}');
    if (recaptchaInput) {
        const form = recaptchaInput.closest('form');
        if (form) {
            // Attach Alpine data if not already set
            if (!form.hasAttribute('x-data')) {
                form.setAttribute('x-data', '{}');
            }
            form.addEventListener('submit', function(e) {
                // Only execute if token is not already set
                if (recaptchaInput.value === '') {
                    e.preventDefault();
                    grecaptcha.ready(() => {
                        grecaptcha.execute('{$this->sitekey}', { action: '{$action}' })
                        .then((token) => {
                            recaptchaInput.value = token;
                            form.submit();
                        });
                    });
                }
            });
        }
    }
});
</script>";
        return $html;
    }

    public function fieldV2() {
        ///
        return '<div class="g-recaptcha" data-sitekey="'.$this->sitekeyv2.'"></div>';
    }



    /**
     * check if server is cloudflare then this method will fetch real user ip becuase cloudare send cloudflare server ip in REMOTE_ADDR
     * @return string
     */
    protected function getRealClientIp()
    {
        if ($this->request->server->has('HTTP_CF_CONNECTING_IP')) {
            return $this->request->server->get('HTTP_CF_CONNECTING_IP');
        }
        return $this->request->getClientIp();
    }


}

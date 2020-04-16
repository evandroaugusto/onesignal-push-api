<?php

namespace evandroaugusto\OneSignal;

use evandroaugusto\HttpClient\HttpClient;

class OneSignalPushAPI
{
    const apiUrl = 'https://onesignal.com/api/v1';

    private $apiKey;
    private $restKey;
    private $client;
    private $notification;
    private $options;


    public function __construct($apiKey, $restKey, HttpClient $client = null)
    {
        if (!$apiKey) {
            throw new \Exception('You must specify your API Key');
        }

        if (!$restKey) {
            throw new \Exception('You must specify your REST Key');
        }

        $this->apiKey = $apiKey;
        $this->restKey = $restKey;

        $this->setDefaultOptions();
        $this->setClient($client);
    }

    /**
     * Create push notification message
     *
     * The parameter must use the same array structure from OneSigal documentation (send API)
     *
     * @param  array $params OneSignal send parameters
     * @param  array $options attributes
     * @return array          success callback
     */
    private function createNotification($params)
    {
        if (!is_array($params)) {
            throw new \Exception('Message is not a valid format');
        }

        $fields = array();
        foreach ($params as $language => $attr) {
            if (isset($attr['title'])) {
                $fields['headings'][$language] = $attr['title'];
            }

            if (!isset($attr['content'])) {
                throw new \Exception('Message content is required');
            }

            $fields['contents'][$language] = $attr['content'];
        }

        $this->setNotification($fields);
        return $fields;
    }

    /**
     * Send push notification
     */
    public function send()
    {
        // check if notification is set
        if (!$this->getNotification()) {
            throw new \Exception('You must set a notification message before sending');
        }

        $fields = $this->prepareNotification();
                
        $attr = array(
            'header' => array(
                'Content-Type: application/json', 'Authorization: Basic ' . $this->getRestKey()
            ),
            'fields' => $fields,
        );
        
        $url = self::apiUrl . '/notifications';

        return $this->client->post($url, $attr);
    }

    /**
     * Prepare notification message converting to JSON object before sending
     */
    private function prepareNotification()
    {
        $notification = $this->getNotification();

        if (!$notification) {
            throw new \Exception('Notification message not set');
        }
            
        // base structure
        $options = $this->getOptions();
        $fields = array(
            'app_id' => $this->getApiKey(),
        ) + $notification + $options;

        return json_encode($fields);
    }

    /**
     * Default options
     */
    protected function setDefaultOptions()
    {
        // see OneSignal documentation
        $options = array(
            'included_segments' => array('All')
        );

        $this->options = $options;
    }

    /**
     * Set http client
     *
     * @param {class} $client
     */
    protected function setClient($client)
    {
        if ($client) {
            $this->client = $client;
        } else {
            $this->client = new HttpClient();
        }
    }

    /**
     * Set send options
     *
     * @param [array] $options
     * @return void
     */
    public function setOptions($options)
    {
        if (!is_array($options)) {
            return false;
        }

        if (isset($options['include_player_ids'])) {
            unset($this->options['included_segments']);
        }

        $this->options = array_merge($this->options, $options);
    }

    /**
     * Getters and Setters
     */
        
    protected function setApiKey($apiKey)
    {
        $this->detectEndpoint($apiKey);
        $this->apiKey = $apiKey;
    }

    protected function getApiKey()
    {
        return $this->apiKey;
    }

    protected function getRestKey()
    {
        return $this->restKey;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setNotification($notification)
    {
        $this->notification = $notification;
    }

    public function getNotification()
    {
        return $this->notification;
    }
}

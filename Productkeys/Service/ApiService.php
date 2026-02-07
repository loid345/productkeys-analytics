<?php

namespace Dart\Productkeys\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class ApiService
{
    protected $_logger;

    public function __construct(\Dart\Productkeys\Logger\Logger $logger)
    {
        $this->_logger = $logger;
    }

    public function sendRequest($endpoint, $method, $authType, $authHeader, $body, $contentType)
    {
        try
        {
            $client = new Client();

            if ($contentType === '0' || $contentType === 'application/json')
            {
                $contentType = 'application/json';
            }
            elseif ($contentType === '1' || $contentType === 'application/xml')
            {
                $contentType = 'application/xml';
            }
            else
            {
                $contentType = 'text/plain';
            }

            if ($method === '0' || $method === 'POST')
            {
                $method = 'POST';
            }
            elseif ($method === '1' || $method === 'PUT')
            {
                $method = 'PUT';
            }
            else
            {
                $method = 'GET';
            }

            if ($authType === '0' || $authType === 'basic')
            {
                $authType = 'basic';
            }
            elseif ($authType === '1' || $authType === 'bearer')
            {
                $authType = 'bearer';
            }
            else
            {
                $authType = 'api_key';
            }

            $headers =
            [
                'Content-Type' => $contentType,
            ];

            if ($authType === 'basic')
            {
                if ($authHeader)
                {
                    $authData = json_decode($authHeader, true);
                    if ($authData && isset($authData['username']) && isset($authData['password']))
                    {
                        $headers['Authorization'] = 'Basic ' . base64_encode($authData['username'] . ':' . $authData['password']);
                    }
                }
            } elseif ($authType === 'bearer')
            {
                if ($authHeader)
                {
                    $headers['Authorization'] = 'Bearer ' . $authHeader;
                }
            }
            elseif ($authType === 'api_key')
            {
                if ($authHeader)
                {
                    $authData = json_decode($authHeader, true);
                    if (is_array($authData))
                    {
                      if (isset($authData[0]['name']) && isset($authData[0]['value']))
                      {
                          foreach ($authData as $apiKey)
                          {
                              if (isset($apiKey['name']) && isset($apiKey['value']))
                              {
                                  $headers[$apiKey['name']] = $apiKey['value'];
                              }
                          }
                      }
                      else
                      {
                        foreach ($authData as $apiKeyName => $apiKeyValue)
                        {
                          $headers[$apiKeyName] = $apiKeyValue;
                        }
                      }
                    }
                }
            }

            $client->request($method, $endpoint,
            [
                'headers' => $headers,
                'body' => $body,
                'http_errors' => false
            ]);
        }
        catch (RequestException $e)
        {
            $this->_logger->error('API Request Error in ApiService Class: ' . $e->getMessage());
        }
        catch (GuzzleException $e)
        {
            $this->_logger->error('GuzzleException in ApiService: ' . $e->getMessage());
        }
    }
}

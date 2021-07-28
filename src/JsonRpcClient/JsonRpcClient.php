<?php

namespace Codeages\Library\JsonRpcClient;

use Datto\JsonRpc\Responses\ErrorResponse;
use Datto\JsonRpc\Responses\ResultResponse;
use GuzzleHttp\Client;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class JsonRpcClient
{
    private $protocol;

    private $http;

    private $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'timeout' =>  60,
        ], $options);
    }

    public function call()
    {
        $args = func_get_args();

        if (count($args) < 2) {
            throw new \InvalidArgumentException("Missing rpc endpoint or  method name.");
        }

        $endpoint = array_shift($args);
        $method = array_shift($args);

        $protocol = $this->getProtocol();
        $http = $this->getHttp();

        $protocol->query($this->makeId(), $method, $args);

        if (empty($endpoint['addr'])) {
            throw new JsonRpcException("Endpoint addr is missing.");
        }

        if (empty($endpoint['auth_type'])) {
            throw new JsonRpcException("Endpoint auth_type is missing.");
        }
        if (!in_array($endpoint['auth_type'], ['basic'])) {
            throw new JsonRpcException("Endpoint auth_type is not supported.");
        }

        if (empty($endpoint['auth_credentials'])) {
            throw new JsonRpcException("Endpoint auth_credentials is missing.");
        }

        if (empty($endpoint['auth_credentials']['username']) || empty($endpoint['auth_credentials']['password'])) {
            throw new JsonRpcException("Endpoint auth_credentials.username or auth_credentials.password is missing.");
        }

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'CodeAges JsonRpc Client 1.0.0',
        ];

        $headers['Authorization'] = sprintf('Basic %s', base64_encode("{$endpoint['auth_credentials']['username']}:{$endpoint['auth_credentials']['password']}"));
        $headers['JsonRpc-Context'] = !empty($endpoint['context']) ? http_build_query($endpoint['context']) : '';
        if (!empty($endpoint['context'])) {
            $headers['JsonRpc-Context'] = http_build_query($endpoint['context']);
        }

        $addr = $endpoint['addr'];
        if (!empty($endpoint['context']['trace_id'])) {
            $addr = $endpoint['addr'] . (strpos($endpoint['addr'], '?') === false ? '?' : '&') . 'trace_id=' . $endpoint['context']['trace_id'];
        }

        try {
            $response = $http->request('POST', $addr, [
                'timeout' => $this->options['timeout'],
                'headers' => $headers,
                'body' => $protocol->encode(),
            ]);
            $content = $response->getBody();
        } catch (ExceptionInterface $e) {
            $detail = null;
            if ($e instanceof ServerExceptionInterface || $e instanceof ClientExceptionInterface) {
                $response = $e->getResponse();
                $detail = [
                    'http_code' => $response->getStatusCode(),
                    'content' => $response->getContent(false),
                    'info' => $response->getInfo(),
                ];
            }
            throw new JsonRpcException($e->getMessage(), $e->getCode(), $detail);
        }

        try {
            $response = $protocol->decode($content);
        } catch (\Exception $e) {
            throw new JsonRpcException($e->getMessage(), $e->getCode());
        }

        $response = array_pop($response);

        if ($response instanceof ResultResponse) {
            return $response->getValue();
        } elseif ($response instanceof  ErrorResponse) {
            $code = $response->getCode();
            if (in_array($code, [-32700, -32600, -32601, -32602])) {
                throw new JsonRpcClientException($response->getMessage(), $code, $response->getData());
            }

            throw new JsonRpcServerException($response->getMessage(), $code, $response->getData());
        }

        throw new JsonRpcException("Invalid json rpc response.");
    }

    private function makeId()
    {
        return sprintf("%f_%d", microtime(true), rand(1000000000, 9999999999));
    }

    private function getProtocol()
    {
        if (!$this->protocol)  {
            $this->protocol = new \Datto\JsonRpc\Client();
        }
        $this->protocol->reset();
        return $this->protocol;
    }

    private function getHttp()
    {
        if (!$this->http)  {
            $this->http = new Client();
        }

        return $this->http;
    }
}

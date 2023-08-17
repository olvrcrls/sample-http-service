<?php

namespace App\Base;


abstract class BaseClient
{
    /**
     * @var HttpService
     */
    protected $httpService;

    /**
     * BaseHttpService constructor.
     *
     * @param HttpService $httpService
     */
    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService;
    }

    protected function setClientCredentialsAuth()
    {
        $credentials = [
            'PHP-AUTH-USER' => config('service.client_credentials.username'),
            'PHP-AUTH-PW' => config('service.client_credentials.password'),
        ]
        $this->httpService->withHeaders($credentials);
    }

    /**
     * Make a get call
     *
     * @param      $url
     * @param  null  $params
     *
     * @param  bool  $isAbsolute
     *
     * @return mixed
     * @throws ClientErrorException
     * @throws ServerErrorException
     */
    protected function get($url, $params = null, $isAbsolute = false)
    {
        $this->httpService->addHeader('Accept', 'application/json');

        $url = $this->buildUrl($url, $isAbsolute);

        [$status, $response, $rawResponse] = $this->httpService->get(
            $url,
            $params
        );

        return $this->handleResponse($url, $status, $response, $rawResponse);
    }


    /**
     * @return string
     */
    abstract public function baseUrl();

    /**
     * Handle the HTTP response
     *
     * @param $url
     * @param $status
     * @param $response
     * @param $rawResponse
     *
     * @return mixed
     * @throws ClientErrorException
     * @throws ServerErrorException
     */
    protected function handleResponse($url, $status, $response, $rawResponse)
    {
        // could not connect
        if ($status === -1) {
            throw new ServerErrorException(
                $this->exceptionErrorMessage($url, $status, 'Could not connect to API')
            );
        }

        // successful response
        if ($status >= 200 && $status < 400) {
            // even though server responded with a success status code, let the client know if the response is non-JSON
            if ($response === null) {
                return [
                    'server-response' => [
                        'message'      => 'Server responded with a non-JSON response',
                        'raw_response' => $rawResponse,
                    ],
                ];
            }

            // all good
            return $response;
        }

        // non-JSON error
        if ($response === null) {
            $this->errorHook($url, $status, $response, $rawResponse);
            throw new ServerErrorException(
                $this->exceptionErrorMessage($url, $status, 'Responded with a non-JSON error'),
                [
                    'raw_response' => $rawResponse,
                ]
            );
        }

        // client error
        if ($status >= 400 && $status < 500) {
            $context = null;
            if (Arr::get($response, 'exception') === NotFoundHttpException::class) {
                $response['message'] = 'Invalid API endpoint.';
            }

            if ($status === 422) {
                $response['message'] = "Invalid data sent to {$this->serviceName()}.";
                $errors              = Arr::get($response, 'errors');
                if ($errors !== null) {
                    $context = [
                        'errors' => $errors,
                    ];
                }
            }

            $this->errorHook($url, $status, $response, $rawResponse);
            throw new ClientErrorException($response['message'], $context);
        }

        // server error
        if ($status >= 500) {
            $this->errorHook($url, $status, $response, $rawResponse);
            throw new ServerErrorException(
                $this->exceptionErrorMessage($url, $status, Arr::get($response, 'message', 'Unhandled error occurred!')),
                isset($response['debug']) ? $response['debug'] : null
            );
        }

        throw new ServerErrorException($this->exceptionErrorMessage($url, $status, 'responded with unknown status code.'), [
            'raw_response' => $rawResponse,
        ]);
    }

    /**
     * Get the full exception error message
     *
     * @param string $url
     * @param string $message
     *
     * @return string
     */
    private function exceptionErrorMessage($url, $status, $message)
    {
        return "[{$this->serviceName()}] status={$status} url={$url} message={$message}";
    }

    /**
     * @return string
     */
    abstract public function serviceName();

    /**
     * @param string     $url
     * @param integer    $status
     * @param array|null $response
     * @param string     $rawResponse
     */
    public function errorHook($url, $status, $response, $rawResponse)
    {
        // no-op
    }

    /**
     * Make a post call
     *
     * @param      $url
     * @param      $data
     * @param  null  $urlParams
     *
     * @param  bool  $isAbsolute
     *
     * @return mixed
     * @throws ClientErrorException
     * @throws ServerErrorException
     */
    protected function post($url, $data, $urlParams = null, $isAbsolute=false)
    {
        $url = $this->buildUrl($url, $isAbsolute);
        [$status, $response, $rawResponse] = $this->httpService->post(
            $url,
            $data,
            $urlParams
        );

        return $this->handleResponse($url, $status, $response, $rawResponse);
    }

    /**
     * Make a post call async
     *
     * @param      $url
     * @param      $data
     * @param  null  $urlParams
     * @param  bool  $isAbsolute
     *
     * @return mixed
     * @throws ClientErrorException
     * @throws ServerErrorException
     */
    protected function postAsync($url, $data, $urlParams = null, $isAbsolute=false)
    {
        $url = $this->buildUrl($url, $isAbsolute);
        [$status, $response, $rawResponse] = $this->httpService->postAsync(
            $url,
            $data,
            $urlParams
        );

        return $this->handleResponse($url, $status, $response, $rawResponse);
    }

    /**
     * Make a put call
     *
     * @param      $url
     * @param      $data
     * @param null $urlParams
     *
     * @return mixed
     * @throws ClientErrorException
     * @throws ServerErrorException
     */
    protected function put($url, $data, $urlParams = null)
    {
        $url = $this->buildUrl($url);
        [$status, $response, $rawResponse] = $this->httpService->put(
            $url,
            $data,
            $urlParams
        );

        return $this->handleResponse($url, $status, $response, $rawResponse);
    }
}

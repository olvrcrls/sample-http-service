<?php

namespace App\Services;

use App\Base\BaseClient;

class CustomerService extends BaseClient
{

    /**
     * @inheritDoc
     */
    public function baseUrl()
    {
        return config('service.customer_service_uri') ?? '/api/v1/customers';
    }

    /**
     * @inheritDoc
     */
    public function serviceName()
    {
        return 'CustomerService';
    }

    /**
     * Send batch process request
     *
     * @param integer $productId
     *
     * @return array
     */
    public function createCustomer(CustomerResource $data)
    {
        $this->setClientCredentialsAuth();

        return $this->post($this->baseUrl(), $data);
    }

    public function updateCustomer($id, CustomerResource $data)
    {
        $this->setClientCredentialsAuth();

        return $this->post($this->baseUrl() . "/update/{$id}", $data);
    }
}

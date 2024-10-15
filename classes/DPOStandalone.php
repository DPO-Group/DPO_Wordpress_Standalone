<?php /** @noinspection PhpMissingStrictTypesDeclarationInspection */

/*
 * Copyright (c) 2024 DPO Group
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the MIT License
 */

require_once 'APIManager.php';
require_once 'CustomerManager.php';

/**
 * DPO standalone class
 */
class DPOStandalone
{
    protected APIManager $apiManager;
    protected CustomerManager $customerManager;
    protected string $companyToken;
    protected string $serviceType;

    /**
     *
     */
    public function __construct()
    {
        $this->apiManager = new APIManager();
        $this->customerManager = new CustomerManager();

        $this->companyToken = get_option('dpo_standalone_company_token');
        $this->serviceType = get_option('dpo_standalone_service_type');
    }

    /**
     * @return string
     */
    public function getCompanyToken(): string
    {
        return $this->companyToken;
    }

    /**
     * @return string
     */
    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiManager->getApiUrl();
    }

    /**
     * @return string
     */
    public function getPayUrl(): string
    {
        return $this->apiManager->getPayUrl();
    }

    // Delegating to APIManager for handling response codes

    /**
     * @param $code
     *
     * @return string
     */
    public function getCreateRes($code): string
    {
        return $this->apiManager->getCreateRes($code);
    }

    /**
     * @param $description
     *
     * @return int|string
     */
    public function getCreateResCode($description): int|string
    {
        return $this->apiManager->getCreateResCode($description);
    }

    /**
     * @param $code
     *
     * @return string
     */
    public function getVerifyRes($code): string
    {
        return $this->apiManager->getVerifyRes($code);
    }

    /**
     * @param $description
     *
     * @return int|string
     */
    public function getVerifyResCode($description): int|string
    {
        return $this->apiManager->getVerifyResCode($description);
    }

    // Delegating customer-related functionality to CustomerManager

    /**
     * @return string
     */
    public function getOrderItems(): string
    {
        return $this->customerManager->getOrderItems();
    }

    /**
     * @return int|string
     */
    public function getDialCode(): int|string
    {
        return $this->customerManager->getDialCode();
    }

    /**
     * @return string
     */
    public function getCustomerZip(): string
    {
        return $this->customerManager->getCustomerZip();
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->customerManager->getAddress();
    }

    /**
     * @return string
     */
    public function getCustomerCity(): string
    {
        return $this->customerManager->getCustomerCity();
    }

    /**
     * @return string
     */
    public function getCustomerPhone(): string
    {
        return $this->customerManager->getCustomerPhone();
    }
}

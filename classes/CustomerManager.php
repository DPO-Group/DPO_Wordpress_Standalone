<?php /** @noinspection PhpMissingStrictTypesDeclarationInspection */

/**
 * Customer manager class
 */
class CustomerManager
{
    /**
     * @return string
     */
    public function getOrderItems(): string
    {
        return get_option('dpo_standalone_item_details') ?? 'Test Item';
    }

    /**
     * @return int|string
     */
    public function getDialCode(): int|string
    {
        return get_option('dpo_standalone_customer_dial_code') ?? 11;
    }

    /**
     * @return string
     */
    public function getCustomerZip(): string
    {
        return get_option('dpo_standalone_customer_zip') ?? '01234';
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return get_option('dpo_standalone_customer_address') ?? 'My Address';
    }

    /**
     * @return string
     */
    public function getCustomerCity(): string
    {
        return get_option('dpo_standalone_customer_city') ?? 'My City';
    }

    /**
     * @return string
     */
    public function getCustomerPhone(): string
    {
        return get_option('dpo_standalone_customer_phone') ?? '5556677';
    }
}

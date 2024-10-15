<?php /** @noinspection PhpMissingStrictTypesDeclarationInspection */

/**
 * API manager class
 */
class APIManager
{
    protected static string $apiUrl = 'https://secure.3gdirectpay.com/API/v6/';
    protected static string $payUrl = 'https://secure.3gdirectpay.com/payv2.php';

    protected array $createResponses = [
        '100' => 'Success',  // Example
        // Add other create response codes here
    ];
    protected array $verifyResponses = [
        '000' => 'Transaction Paid',
        // Other verification response codes
    ];

    /**
     *
     */
    public function __construct()
    {
        $this->apiUrl = self::$apiUrl;
        $this->payUrl = self::$payUrl;
        $this->createResCodes = array_flip($this->createResponses);
        $this->verifyResCodes = array_flip($this->verifyResponses);
    }

    /**
     * Get created response code
     *
     * @param $code
     *
     * @return string
     */
    public function getCreateRes($code): string
    {
        return $this->createResponses[$code] ?? 'Unknown code';
    }

    /**
     * @param $description
     *
     * @return int|string
     */
    public function getCreateResCode($description): int|string
    {
        return $this->createResCodes[$description] ?? 'Unknown description';
    }

    /**
     * Get the verification reponse
     *
     * @param $code
     *
     * @return string
     */
    public function getVerifyRes($code): string
    {
        return $this->verifyResponses[$code] ?? 'Unknown code';
    }

    /**
     * Get verification reponse code
     *
     * @param $description
     *
     * @return int|string
     */
    public function getVerifyResCode($description): int|string
    {
        return $this->verifyResCodes[$description] ?? 'Unknown description';
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @return string
     */
    public function getPayUrl(): string
    {
        return $this->payUrl;
    }
}
